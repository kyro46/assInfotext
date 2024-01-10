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
    final function getPluginName(): string
    {
			return "assInfotext";
		}
		
		final function getQuestionType()
		{
			return "assInfotext";
		}
		
		final function getQuestionTypeTranslation(): string
		{
			return $this->txt($this->getQuestionType());
		}
}
?>