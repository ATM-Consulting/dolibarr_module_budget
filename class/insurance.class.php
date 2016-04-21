<?php
    
/**
 * Class TBudget
 */
class TInsurance extends TObjetStd {
		
	public $percentage;
	public $date_debut;
	public $date_fin;
	public $fk_project;
	public $label;
	public $TResultat;
	
	
	
	function __construct(){
		
		parent::set_table(MAIN_DB_PREFIX.'sig_insurance');
		parent::add_champs('date_debut, date_fin',array('type'=>'date', 'index'=>true));
		parent::add_champs('fk_project',array('type'=>'integer', 'index'=>true));
		parent::add_champs('percentage',array('type'=>'float'));
		
		parent::_init_vars('label');
		parent::start();
		
		$this->setChild('TInsuranceLines','fk_insurance');
	}
	
	
	static function getInsurance(&$PDOdb, $fk_project, $date_deb, $date_fin) {
		$sql = "SELECT rowid";
		$sql.=" FROM ".MAIN_DB_PREFIX."sig_insurance";
		if(!is_array($fk_project))
			$sql.=" WHERE fk_project=".$fk_project;
		else
			$sql.=" WHERE fk_project IN (".implode(',', $fk_project).")";
		$sql.=" ORDER BY date_debut ";
		$Tab = $PDOdb->ExecuteAsArray($sql);
		
		$TInsurance = $TPercents = array();
		foreach($Tab as $row) {
			$insurance=new TInsurance;
			$insurance->load($PDOdb, $row->rowid);
			$insurance->fetch_resultat($date_deb, $date_fin);
			if(!empty($insurance->TResultat['allpercent']))
				$TPercents = array_merge($TPercents,$insurance->TResultat['allpercent']);
			$TInsurance[] = $insurance->TResultat;
		}
		$TInsurance['allpercent'] = $TPercents;
		//pre($TInsurance,true);
		return $TInsurance;
	}
	
	
	function fetch_resultat($date_deb, $date_fin) {
		
		
		$TDates=getTDatesByDates($date_deb, $date_fin);
		$TAllCateg = TCategComptable::getStructureCodeComptable();

		$this->TResultat['libelle'] = $this->label;
		$this->TResultat['date'] = date('d/m/Y',$this->date_debut);
		$this->TResultat['year'] = date('Y',$this->date_debut);
		$this->TResultat['month'] = (int) date('m',$this->date_debut);
		
		foreach ($TDates as $year=>$TMonth) {
			foreach($TMonth as $iMonth=>$month) {
				foreach($TAllCateg as $label=>$TCateg) {
					if(!empty($TCateg['subcategory'])) {
						foreach($TCateg['subcategory'] as $TSubCateg)
						{
							$code_compta = $TSubCateg['code_compta'];
							$percentage = $this->getAmountForCode($code_compta);
							if($percentage > 0) {
								$this->TResultat['category'][_get_key($label)]['@bymonth'][$year][$iMonth]['subcategory'][_get_key($TSubCateg['libelle'])]['libelle'] = $TSubCateg['label'];
								$this->TResultat['category'][_get_key($label)]['@bymonth'][$year][$iMonth]['subcategory'][_get_key($TSubCateg['libelle'])]['code_compta'] = $code_compta;
								$this->TResultat['category'][_get_key($label)]['@bymonth'][$year][$iMonth]['subcategory'][_get_key($TSubCateg['libelle'])]['percentage'] = $percentage;
								$this->TResultat['allpercent'][$percentage] = $percentage;
							}
						}
					}
				}
			}
		}
		//pre($this->TResultat,true);
	}


	function regroup_taux_insurance(){
		
		
	}
	
	
	function getAmountForCode($code_compta) {
		
		foreach($this->TInsuranceLines as &$l) {
			if($l->code_compta == $code_compta) {
				return $l->percentage;
			}
		}
		
	}

	function setAmountForCode($code_compta,$percentage) {
		foreach($this->TInsuranceLines as $k=> &$l) {
			if($l->code_compta == $code_compta) {
				$l->percentage = $percentage;	
				return $k;		
			}
		}
		
		$PDOdb=new stdClass;
		$k = $this->addChild($PDOdb, 'TInsuranceLines');
		
		$this->TInsuranceLines[$k]->code_compta = $code_compta;
		$this->TInsuranceLines[$k]->percentage = $percentage;
		
		return $k;
	}
	
	
}



class TInsuranceLines extends TObjetStd{
	
	function __construct() {
		global $langs;
		
        parent::set_table(MAIN_DB_PREFIX.'sig_insurance_line');
		parent::add_champs('code_compta');
		parent::add_champs('percentage',array('type'=>'float'));
		parent::add_champs('fk_insurance',array('type'=>'integer', 'index'=>true));
		
		
		parent::_init_vars();
        parent::start();

	}
}
