<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Department;
use App\Models\DiagnosticTest;
use App\Models\Doctor;
use App\Models\GalleryAlbum;
use App\Models\HealthPackage;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\Post;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * What the Ctrl+K palette finds beyond the menu: the records themselves.
 *
 * The palette's own list is a registry of pages, which answers "where is the
 * doctors screen" and not "where is Dr Farhana" — the question the front desk
 * actually has, usually holding a phone with a booking reference on it.
 *
 * Like `ManagedLists`, this is a **whitelist rather than a lookup**. The term
 * arrives from the browser; the model, the columns it may match and the screen
 * it links to are all declared here, so "search anything" cannot become a way
 * to read a column nobody meant to publish.
 *
 * Sections the signed-in role cannot reach are not searched at all — the
 * palette must not be a way around the menu it sits above.
 *
 * Everything is capped at `PER_SOURCE` rows. A palette is a way to reach one
 * record, not a report — somebody who wants all of them wants the listing, and
 * its own filters.
 */
class PanelSearch
{
    /** Rows per section. Twelve sections, so the whole answer stays readable. */
    public const PER_SOURCE = 4;

    private const MIN_TERM = 2;

    /**
     * key      the menu key, which is also the group label and the icon
     * columns  what may be matched; translatable ones match in both locales
     * route    the screen a hit opens
     * label    the column the row is named by
     */
    private const SOURCES = [
        ['key' => 'appointments', 'model' => Appointment::class, 'columns' => ['reference', 'patient_name', 'phone'], 'route' => 'admin.appointments.show', 'label' => 'patient_name'],
        ['key' => 'patients', 'model' => Patient::class, 'columns' => ['name', 'phone', 'email'], 'route' => 'admin.patients.documents', 'label' => 'name'],
        ['key' => 'documents', 'model' => PatientDocument::class, 'columns' => ['title', 'phone'], 'route' => 'admin.documents.edit', 'label' => 'title'],
        ['key' => 'messages', 'model' => ContactMessage::class, 'columns' => ['name', 'phone', 'subject'], 'route' => 'admin.messages.show', 'label' => 'name'],
        ['key' => 'doctors', 'model' => Doctor::class, 'columns' => ['name', 'speciality', 'designation', 'qualifications'], 'route' => 'admin.doctors.edit', 'label' => 'name'],
        ['key' => 'departments', 'model' => Department::class, 'columns' => ['name', 'tagline'], 'route' => 'admin.departments.edit', 'label' => 'name'],
        ['key' => 'services', 'model' => Service::class, 'columns' => ['name', 'summary'], 'route' => 'admin.services.edit', 'label' => 'name'],
        ['key' => 'packages', 'model' => HealthPackage::class, 'columns' => ['name', 'summary'], 'route' => 'admin.packages.edit', 'label' => 'name'],
        ['key' => 'diagnostics', 'model' => DiagnosticTest::class, 'columns' => ['name', 'code'], 'route' => 'admin.diagnostics.edit', 'label' => 'name'],
        ['key' => 'posts', 'model' => Post::class, 'columns' => ['title', 'excerpt'], 'route' => 'admin.posts.edit', 'label' => 'title'],
        ['key' => 'gallery', 'model' => GalleryAlbum::class, 'columns' => ['title', 'summary'], 'route' => 'admin.gallery.edit', 'label' => 'title'],
        ['key' => 'testimonials', 'model' => Testimonial::class, 'columns' => ['patient_name', 'treatment'], 'route' => 'admin.testimonials.edit', 'label' => 'patient_name'],
    ];

    /**
     * @return list<array{kind: string, label: string, meta: ?string, group: string, url: string}>
     */
    public static function run(?string $term): array
    {
        $term = trim((string) $term);

        if (mb_strlen($term) < self::MIN_TERM) {
            return [];
        }

        $user = auth('web')->user();
        $results = [];

        foreach (self::SOURCES as $source) {
            /* The same permission the menu and the routes use. Without it the
               palette is a way around every one of them: an editor typing a
               mobile number would be handed the patient, their bookings and
               the documents published to them, from a box on every screen. */
            if (! $user?->canReach($source['key'])) {
                continue;
            }

            foreach (self::query($source, $term) as $record) {
                $results[] = [
                    'kind' => 'record',
                    'label' => (string) ($record->{$source['label']} ?: __("admin.nav.{$source['key']}")),
                    'meta' => self::meta($source['key'], $record),
                    'group' => __("admin.nav.{$source['key']}"),
                    'url' => route($source['route'], $record),
                ];
            }
        }

        return $results;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Model> */
    private static function query(array $source, string $term): mixed
    {
        /** @var Model $model */
        $model = new $source['model'];

        // The term is a value, never SQL — but `%` and `_` inside it are
        // wildcards to LIKE, so a phone number typed with an underscore would
        // quietly match more than it was asked to.
        $like = '%'.addcslashes($term, '%_\\').'%';

        $translatable = method_exists($model, 'translatableAttributes')
            ? $model->translatableAttributes()
            : [];

        return $model::query()
            ->where(function (Builder $query) use ($source, $translatable, $like) {
                foreach ($source['columns'] as $column) {
                    in_array($column, $translatable, true)
                        // Both scripts stay findable whichever locale is
                        // active: staff routinely type an English name on a
                        // Bangla page. Same rule as Doctor::search().
                        ? $query->orWhereTranslatableLike($column, $like)
                        : $query->orWhere($column, 'like', $like);
                }
            })
            ->latest('id')
            ->limit(self::PER_SOURCE)
            ->get();
    }

    /** The second line of a row: whatever tells two similar records apart. */
    private static function meta(string $key, Model $record): ?string
    {
        return match ($key) {
            // A reference is what the patient is reading down the phone, and
            // the date is what makes two bookings by one person different.
            'appointments' => $record->reference.' · '.$record->appointment_date->translatedFormat('j M Y'),
            'patients', 'messages' => $record->phone,
            'documents' => $record->phone,
            'doctors' => $record->speciality,
            'diagnostics' => $record->code,
            'testimonials' => $record->treatment,
            default => null,
        };
    }
}
