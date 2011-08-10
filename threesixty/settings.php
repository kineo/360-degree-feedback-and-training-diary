<?php

$settings->add(new admin_setting_configtextarea('threesixty_selftypes', get_string('setting:selftypes', 'threesixty'), get_string('setting:selftypesdesc', 'threesixty'), get_string('setting:selftypesdefault', 'threesixty'), PARAM_TEXT, 60, 8));
$settings->add(new admin_setting_configtextarea('threesixty_respondenttypes', get_string('setting:respondenttypes', 'threesixty'), get_string('setting:respondenttypesdesc', 'threesixty'), get_string('setting:respondenttypesdefault', 'threesixty'), PARAM_TEXT, 60, 8));
