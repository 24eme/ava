<?php

class DRevPrelevementForm extends acCouchdbObjectForm
{

    public function configure() {
       $this->setWidgets(array(
            "date" => new sfWidgetFormInput(array()),
        ));

        $this->setValidators(array(
            "date" => new sfValidatorDate(array('required' => false)),
        ));

        $this->widgetSchema["date"]->setLabel('Semaine du');
    }
}