<?php


namespace local_userimport\form;

class import extends \moodleform
{

    /**
     * @inheritDoc
     */
    protected function definition()
    {
        $mform = $this->_form;

        $mform->addElement('html', '<div class="parametrs">'.get_string('parametrs', 'local_userimport').'</div>');
        $options = array(
            'noselectionstring' => '',
            'placeholder' => 'p_course',
        );
        $mform->addElement('text', 'p_course', get_string('course'), $options); // Add elements to your form.
        $mform->addRule('p_course', get_string('courseidnotset', 'local_userimport'), 'required', null, 'client');
        $mform->setType('p_course', PARAM_INT);

        $curretyear = userdate(time(), '%Y');
        $curretmonth = userdate(time(), '%m');
        $curretyear = ($curretmonth < 9) ? ($curretyear-1) : $curretyear ;
        $startyear = ($curretyear-6) ;

        $yearsarray = range($startyear, $curretyear);
        $yearslist = array_reverse(array_combine($yearsarray, $yearsarray),true);
        $options['placeholder'] =  'p_year';
        $mform->addElement('autocomplete', 'p_year', '', $yearslist, $options);

        $semestrlist = array('1'=>1,'2'=>2);
        $options['placeholder'] =  'p_semester';
        $mform->addElement('autocomplete', 'p_semester', '', $semestrlist, $options);

        $mform->addElement('html', '<div class="importto">'.get_string('importto', 'local_userimport').'</div>');

        $coursecategoryes = \core_course_category::get_all();
        $courseslist = array();
        foreach ($coursecategoryes as $category) {
            if($courses = $category->get_courses()){
                foreach($courses as $course) {
                    $courseslist[$course->id] = $course->fullname;
                }
            }
        }

        $options = array(
            'multiple' => false,
            'noselectionstring' => '',
            'placeholder' => get_string('course'),
        );
        $mform->addElement('autocomplete', 'courseid', '', $courseslist, $options);


        $buttonarray = array();
        $classarray = array('class' => 'form-submit');
        $buttonarray[] = &$mform->createElement('submit', 'saveandreturn', get_string('load', 'local_userimport'), $classarray);
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}