<?php

return [
    'title' => 'Languages', 'description' => 'Interface readiness and active languages for translated data. Only administrators can change these settings.',
    'name' => 'Language', 'code' => 'Code', 'direction' => 'Direction', 'active' => 'Active', 'default' => 'Default',
    'enabled' => 'Enabled', 'disabled' => 'Disabled', 'yes' => 'Default', 'no' => 'Not default',
    'saved' => 'Language settings updated.', 'invalid' => 'Choose a valid setting and boolean value.',
    'keep_default' => 'Select another default language instead of clearing the current one.',
    'activate_first' => 'Activate this language before setting it as default.',
    'choose_default' => 'Select another default language before disabling this language.',
    'not_ready' => 'This language is not ready. Prepare all interface translation keys, direction and local font assets first.',
    'inactive_payload' => 'Translations for inactive or unknown languages are not accepted.',
    'uninitialized' => 'Initialize the language tables with LanguagesSeeder first.',
    'unknown' => 'The save result is unknown. Refresh this page before making further changes.',
    'failed' => 'The change could not be saved. The current server state is shown.',
    'network' => 'Could not confirm the server state. Refresh the page before continuing.',
    'busy' => 'Saving…', 'ltr' => 'LTR', 'rtl' => 'RTL',
];
