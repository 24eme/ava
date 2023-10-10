<?php
class DegustationTourneesForm extends acCouchdbForm
{
    private $lots_par_logements;
    private $regions = null;
    private $secteur = null;
    private $firstLot = [];
    private $nbLots = [];

    public function __construct(Degustation $degustation, $secteur, $options = array(), $CSRFSecret = null)
    {
        $this->secteur = $secteur;
        $this->lots_par_logements = $degustation->getLotsBySecteur();
        $defaults = [];
        foreach($this->lots_par_logements[$this->secteur] as $key => $lots) {
            $defaults[$key] = $this->secteur;
            $this->firstLot[$key] = $lots[0];
            $this->nbLots[$key] = count($lots);
        }

        parent::__construct($degustation, $defaults, $options, $CSRFSecret);
    }

    private function getSecteurs() {
        $secteurs = [DegustationClient::DEGUSTATION_SANS_SECTEUR => ''];
        foreach ($this->lots_par_logements as $secteur => $lots) {
            if ($secteur == DegustationClient::DEGUSTATION_SANS_SECTEUR) {
                continue;
            }
            $secteurs[$secteur] = $secteur;
        }
        return $secteurs;
    }

    public function configure()
    {
        $logements = [];

        foreach ($this->lots_par_logements[$this->secteur] as $logementKey => $lots) {
            $logements[$logementKey] = $lots;
        }

        uasort($logements, function ($a, $b) {
            if (($a[0])->prelevement_heure == ($b[0])->prelevement_heure) return 0;
            return (($a[0])->prelevement_heure < ($b[0])->prelevement_heure)? -1 : 1;
        });

        foreach($logements as $logementKey => $lots) {
            $form = new BaseForm();
            $form->setWidget('heure', new bsWidgetFormInput(array("type"=>'time'), ['class' => 'form-control select2SubmitOnBlur']));
            $form->setValidator('heure', new sfValidatorTime(array('time_output' => 'H:i', 'time_format' => '~(?<hour>\d{2}):(?P<minute>\d{2})~', 'required' => false)));
            $form->setWidget('logement', new bsWidgetFormChoice(['choices' => $this->getSecteurs()], ['class' => 'form-control select2SubmitOnChange']));
            $form->setValidator('logement', new sfValidatorChoice(['choices' => array_keys($this->getSecteurs())]));
            if ($heure = $this->getFirstLot($logementKey)->prelevement_heure) {
                $form->setDefault('heure', $heure);
            }
            $this->embedForm($logementKey, $form);
        }

        $this->widgetSchema->setNameFormat('degustation_tournees[%s]');
    }

    public function save()
    {
        $values = $this->getValues();

        foreach($this->lots_par_logements[$this->secteur] as $logementKey => $lots) {
            foreach($lots as $lot) {
                if (isset($values[$logementKey])) {
                    $lot->prelevement_heure = $values[$logementKey]['heure'];
                    $lot->secteur = ($values[$logementKey]['logement'] != DegustationClient::DEGUSTATION_SANS_SECTEUR) ? $values[$logementKey]['logement'] : null;
                }
            }
        }

        $this->doc->save(false);
    }

    public function getFirstLot($key) {
        return (isset($this->firstLot[$key]))? $this->firstLot[$key] : null;
    }

    public function getNbLots($key) {
        return (isset($this->nbLots[$key]))? $this->nbLots[$key] : null;
    }

}
