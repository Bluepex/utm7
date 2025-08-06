<?php

class LanguageLoader
{
    function initialize() {
        loadLanguages(['general', 'dashboard', 'reports', 'template', 'utm', 'webfilter', 'langSwitch', 'tools/categorization']);
    }
}
