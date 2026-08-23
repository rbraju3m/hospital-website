<?php

namespace App\Sms;

/**
 * Segment arithmetic.
 *
 * Operators bill per segment, and Bangla text is not Latin: anything outside
 * the GSM 03.38 alphabet forces the whole message into UCS-2, where a segment
 * is 70 characters instead of 160. A Bangla confirmation therefore costs two
 * or three times what the same message costs in English, which is worth
 * knowing before writing a longer template.
 */
class SmsText
{
    /** GSM 03.38 basic set plus the extension table, which costs two characters each. */
    private const GSM_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?"
        ."¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    private const GSM_EXTENDED = "^{}\\[~]|€";

    public static function isUnicode(string $text): bool
    {
        foreach (mb_str_split($text) as $character) {
            if (! str_contains(self::GSM_BASIC, $character) && ! str_contains(self::GSM_EXTENDED, $character)) {
                return true;
            }
        }

        return false;
    }

    public static function segments(string $text): int
    {
        if (self::isUnicode($text)) {
            $length = mb_strlen($text);

            return $length <= 70 ? 1 : (int) ceil($length / 67);
        }

        $length = self::gsmLength($text);

        return $length <= 160 ? 1 : (int) ceil($length / 153);
    }

    /** Characters from the extension table occupy two positions, not one. */
    private static function gsmLength(string $text): int
    {
        $length = 0;

        foreach (mb_str_split($text) as $character) {
            $length += str_contains(self::GSM_EXTENDED, $character) ? 2 : 1;
        }

        return $length;
    }
}
