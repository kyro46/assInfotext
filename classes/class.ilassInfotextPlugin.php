<?php

include_once "./Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php";
	
/**
* Question plugin Infotext
*
* @author Christoph Jobst <christoph.jobst@llz.uni-halle.de>
* @version $Id$
* @ingroup ModulesTestQuestionPool
*/
class ilassInfotextPlugin extends ilQuestionsPlugin
{
		final function getPluginName()
		{
			return "assInfotext";
		}
		
		final function getQuestionType()
		{
			return "assInfotext";
		}
		
		final function getQuestionTypeTranslation()
		{
			return $this->txt($this->getQuestionType());
		}
}
?>