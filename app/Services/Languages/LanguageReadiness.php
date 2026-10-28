<?php

namespace App\Services\Languages;

use Illuminate\Support\Arr;

class LanguageReadiness
{
    private array $checked = [];

    public function ready(string $code, string $direction): bool
    {
        return $this->checked[$code.':'.$direction] ??= $this->check($code, $direction);
    }

    private function check(string $code, string $direction): bool
    {
        $support = config('languages.supported.'.$code);
        if (! $support || $support['direction'] !== $direction) {
            return false;
        }
        $suffix = $direction === 'rtl' ? '.rtl' : '';
        if (! is_file(public_path('vendor/tabler/css/tabler'.$suffix.'.min.css'))) {
            return false;
        }
        foreach ([400, 600] as $weight) {
            if (! is_file(public_path('vendor/tabler/fonts/'.$support['font'].'/'.$weight.'.css'))) {
                return false;
            }
        }
        foreach (glob(lang_path('en/*.php')) as $source) {
            $target = lang_path($code.'/'.basename($source));
            if (! is_file($target)) {
                return false;
            }
            $base = require $source;
            $translated = require $target;
            if (! is_array($translated) || array_diff_key(Arr::dot($base), Arr::dot($translated))) {
                return false;
            }
            foreach (Arr::dot($translated) as $value) {
                if (! is_string($value) || trim($value) === '') {
                    return false;
                }
            }
        }

        return true;
    }
}
