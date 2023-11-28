<?php
class CourrierExtrasNouveauForm extends acCouchdbObjectForm {

    public function configure() {

        $fields = array('agent_nom' => array('label' => 'Nom de l\'agent', 'type' => 'text'),
                            'representant_nom' => array('label' => 'Nom du représentant', 'type' => 'text'),
                            'representant_fonction' => array('label' => 'Fonction du représentant', 'type' => 'text'),
                            'analytique_date' => array('label' => 'Date d\'examen analytique', 'type' => 'date'),
                            'analytique_conforme' => array('label' => 'Conformité', 'type' => 'checkbox'),
                            'analytique_libelle' => array('label' => 'Libellé manquement examen analytique', 'type' => 'text'),
                            'analytique_code' => array('label' => 'Code manquement examen analytique', 'type' => 'text'),
                            'analytique_niveau' => array('label' => 'Niveau de gravité test analytique', 'type' => 'text'),
                            'organoleptique_code' => array('label' => 'Code manquement examen organoleptique', 'type' => 'text'),
                            'organoleptique_niveau' => array('label' => 'Niveau de gravité test organoleptique', 'type' => 'text'),
                            'vin_emplacement' => array('label' => 'Emplacement du vin', 'type' => 'text')
                        );

        foreach ($fields as $name => $options_array) {
            if ($options_array['type'] == 'text') {
                $this->setWidget($name, new bsWidgetFormInput($options_array));
                $this->setValidator($name, new sfValidatorString(array('required' => false)));
            } elseif ($options_array['type'] == 'date') {
                $this->setWidget($name, new bsWidgetFormInput(array(), array()));
                $this->setValidator($name, new sfValidatorDate(array('datetime_output' => 'Y-m-d', 'date_format' => '~(?<day>\d{2})/(?P<month>\d{2})/(?P<year>\d{4})~', 'required' => true)));
            } else {
                $this->setWidget($name, new sfWidgetFormInputCheckbox($options_array));
                $this->setValidator($name, new sfValidatorBoolean());
            }
        }

        $this->widgetSchema->setNameFormat('courrier_extras_creation[%s]');

    }


}
