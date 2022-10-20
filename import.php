<?php

use local_userimport\form\import;

require_once('../../config.php');

global $DB;

require_login();
$context = context_system::instance();
if (has_capability('local/userimport:manage', $context)) {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/local/userimport/import.php'));
    $heading = new lang_string('userimport', 'local_userimport');
    $PAGE->set_heading($heading);
    $PAGE->set_title($heading);
    $PAGE->requires->css('/local/userimport/import.css');

    echo $OUTPUT->header();

    $mform = new import();

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/admin/category.php?category=local_userimport'));
    } else if ($data = $mform->get_data()) {
        echo '<span class="content">';
        $import = new \local_userimport\userimport();
        $import->insert_users($data);
        echo '</span>';
    }
    $mform->display();

    echo $OUTPUT->footer();
}