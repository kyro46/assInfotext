<?php
/**
* Question plugin Infotext
*
* @author Christoph Jobst <iliasplugins.christoph.jobst@outlook.de>
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