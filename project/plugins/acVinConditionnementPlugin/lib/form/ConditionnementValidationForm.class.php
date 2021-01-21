<?php

class ConditionnementValidationForm extends acCouchdbForm
{
    public function configure() {
        if(!$this->getDocument()->isPapier()) {
            $engagements = $this->getOption('engagements');
            foreach ($engagements as $engagement) {
                $this->setWidget('engagement_'.$engagement->getCode(), new sfWidgetFormInputCheckbox());
                $this->setValidator('engagement_'.$engagement->getCode(), new sfValidatorBoolean(array('required' => true)));
                if (preg_match('/_OUEX_/', $engagement->getCode())) {
                    $this->getValidator('engagement_'.$engagement->getCode())->setOption('required', false);
                }
            }

            if (DrevConfiguration::getInstance()->hasDegustation()) {
                $this->setWidget('date_degustation_voulue', new sfWidgetFormInput(array(), array()));
                $this->setValidator('date_degustation_voulue', new sfValidatorDate(array('with_time' => false, 'datetime_output' => 'Y-m-d', 'date_format' => '~(?<day>\d{2})/(?P<month>\d{2})/(?P<year>\d{4})~', 'required' => true)));

                if ($this->getDocument()->exist('date_degustation_voulue') && $this->getDocument()->date_degustation_voulue !== null) {
                    $this->setDefault('date_degustation_voulue', DateTime::createFromFormat('Y-m-d', $this->getDocument()->date_degustation_voulue)->format('d/m/Y'));
                } else {
                    $this->setDefault('date_degustation_voulue', (new DateTime())->format('d/m/Y'));
                }
            }
        }

        if($this->getDocument()->isPapier()) {
            $this->setWidget('date', new sfWidgetFormInput());
            $this->setValidator('date', new sfValidatorDate(array('date_output' => 'Y-m-d', 'date_format' => '~(?P<day>\d{2})/(?P<month>\d{2})/(?P<year>\d{4})~', 'required' => true)));
            $this->getWidget('date')->setLabel("Date de réception du document");
            $this->getValidator('date')->setMessage("required", "La date de réception du document est requise");
        }

        $this->widgetSchema->setNameFormat('validation[%s]');
    }

    public function save() {
  		$values = $this->getValues();
  	   $this->getDocument()->getOrAdd("date_degustation_voulue");
       $this->getDocument()->date_degustation_voulue = $values["date_degustation_voulue"];
       $this->getDocument()->save();
  	}
}
