<?php

/**
 * Infotext GUI class for question type plugins
 *
 * @author	Christoph Jobst <iliasplugins.christoph.jobst@outlook.de>
 * @version	$Id:  $
 * @ingroup ModulesTestQuestionPool
 *
 * @ilctrl_iscalledby assInfotextGUI: ilObjQuestionPoolGUI, ilObjTestGUI, ilQuestionEditGUI, ilTestExpressPageObjectGUI
 * @ilctrl_calls assInfotextGUI: ilFormPropertyDispatchGUI
 */
class assInfotextGUI extends assQuestionGUI implements ilGuiQuestionScoringAdjustable
{
	/**
	 * @var ilassInfotextPlugin	The plugin object
	 */
	var $plugin = null;


	/**
	 * @var assInfotext	The question object
	 */
	public assQuestion $object;
	
	/**
	 * Constructor
	 *
	 * @param integer $id The database id of a question object
	 * @access public
	 */
	public function __construct($id = -1)
	{
	    global $DIC;
	    
	    parent::__construct();
	    
	    /** @var ilComponentFactory $component_factory */
	    $component_factory = $DIC["component.factory"];
	    $this->plugin = $component_factory->getPlugin('infotext');
	    $this->object = new assInfotext();
	    if ($id >= 0)
	    {
	        $this->object->loadFromDb($id);
	    }
	}
	
	/**
	 * Creates an output of the edit form for the question
	 *
	 * @param bool $checkonly
	 * @return bool
	 */
	public function editQuestion(bool $checkonly = false, ?bool $is_save_cmd = null): bool
	{
	    global $DIC;
	    $lng = $DIC->language();
	    
	    $this->getQuestionTemplate();
	    $plugin = $this->object->getPlugin();
	    
	    $save = $is_save_cmd ?? $this->isSaveCommand();
		
		$form = new ilPropertyFormGUI();
		$form->setFormAction($this->ctrl->getFormAction($this));
		$form->setTitle($this->outQuestionType());
		$form->setMultipart(TRUE);
		$form->setTableWidth("100%");
		$form->setId("infotext");

		$this->addBasicQuestionFormProperties($form);
		$this->populateQuestionSpecificFormPart($form);
		$this->populateAnswerSpecificFormPart($form);
		$this->populateTaxonomyFormSection($form);
		$this->addQuestionFormCommandButtons($form);

		$errors = false;

		if ($save)
		{
			$form->setValuesByPost();
			$errors = !$form->checkInput();
			$form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and we need this if we don't want to have duplication of backslashes
				
			if ($errors) {
			    $checkonly = false;
			}
		}
		
		if (!$checkonly)
		{
		    $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
		}

		return $errors;
	}

	/**
	 * Evaluates a posted edit form and writes the form data in the question object
	 *
	 * @param bool $always
	 * @return integer A positive value, if one of the required fields wasn't set, else 0
	 */
	protected function writePostData($always = false): int
	{
		$hasErrors = (!$always) ? $this->editQuestion(true) : false;
		if (!$hasErrors)
		{
			$this->writeQuestionGenericPostData();
			$this->object->setPoints( str_replace( ",", ".", $_POST["points"] ));
			$this->saveTaxonomyAssignments();
			return 0;
		}
		return 1;
	}

	/**
	 * Get the HTML output of the question for a test
	 * (this function could be private)
	 * 
	 * @param integer $active_id			           The active user id
	 * @param integer $pass					           The test pass
	 * @param boolean $is_question_postponed           Question is postponed
	 * @param boolean $user_post_solutions	           Use post solutions
	 * @param boolean $show_specific_inline_feedback   Show a feedback
	 * @return string
	 */
    public function getTestOutput(
        int $active_id,
        int $pass,
        bool $is_question_postponed = false,
        array|bool $user_post_solutions = false,
        bool $show_specific_inline_feedback = false
        ): string {
		// no solution to show for assInfotext
		if ($active_id)
		{
			$template = new ilTemplate("tpl.il_as_qpl_infotext_output.html", true, true, 'public/Customizing/global/plugins/Modules/TestQuestionPool/Questions/assInfotext');
			$template->setVariable("QUESTIONTEXT", self::prepareTextareaOutput( $this->object->getQuestion(), TRUE));
		}

		$questionoutput = $template->get();
		$pageoutput = $this->outQuestionPage("", $is_question_postponed, $active_id, $questionoutput);
		return $pageoutput;
	}

	
	/**
	 * Get the output for question preview
	 * (called from ilObjQuestionPoolGUI)
	 * 
	 * @param boolean	show only the question instead of embedding page (true/false)
	 */
	public function getPreview(bool $show_question_only = false, bool $show_inline_feedback = false): string
	{
	    $template = new ilTemplate("tpl.il_as_qpl_infotext_output.html", true, true, 'public/Customizing/global/plugins/Modules/TestQuestionPool/Questions/assInfotext');
	    
		$template->setVariable("QUESTIONTEXT", self::prepareTextareaOutput( $this->object->getQuestion(), TRUE));
		
		$questionoutput = $template->get();
		if(!$show_question_only)
		{
			// get page object output
			$questionoutput = $this->getILIASPage($questionoutput);
		}
		return $questionoutput;
	}

	/**
	 * Get the question solution output
	 * @param integer $active_id             The active user id
	 * @param integer $pass                  The test pass
	 * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
	 * @param boolean $result_output         Show the reached points for parts of the question
	 * @param boolean $show_question_only    Show the question without the ILIAS content around
	 * @param boolean $show_feedback         Show the question feedback
	 * @param boolean $show_correct_solution Show the correct solution instead of the user solution
	 * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
	 * @return string solution output of the question as HTML code
	 */
	function getSolutionOutput(
	    int $active_id,
	    ?int $pass = null,
	    bool $graphical_output = false,
	    bool $result_output = false,
	    bool $show_question_only = true,
	    bool $show_feedback = false,
	    bool $show_correct_solution = false,
	    bool $show_manual_scoring = false,
	    bool $show_question_text = true,
	    bool $show_inline_feedback = true
	 ): string
	{	
		if ($show_correct_solution)
		{
			return $this->object->getPlugin()->txt("bestSolutionNa");
		}
		
		// get the solution template
		$template = new ilTemplate("tpl.il_as_qpl_infotext_output.html", true, true, 'public/Customizing/global/plugins/Modules/TestQuestionPool/Questions/assInfotext');
		

		$questiontext = $this->object->getQuestion();
		if ($show_question_text==true)
		{
		    $template->setVariable("QUESTIONTEXT", self::prepareTextareaOutput( $this->object->getQuestion(), TRUE));
		}
		
		// statt self:: ginge auch $this->object->getQuestionForHTMLOutput());
		$template->setVariable("QUESTIONTEXT", self::prepareTextareaOutput( $this->object->getQuestion(), TRUE));

		$questionoutput   = $template->get();

		$solutiontemplate = new ilTemplate("tpl.il_as_tst_solution_output.html", TRUE, TRUE, "components/ILIAS/TestQuestionPool");
		$solutiontemplate->setVariable("SOLUTION_OUTPUT", $questionoutput);

		$feedback = ($show_feedback) ? $this->getGenericFeedbackOutput($active_id, $pass) : "";
		if (strlen($feedback)) $solutiontemplate->setVariable("FEEDBACK", $this->object->prepareTextareaOutput( $feedback, true ));

		$solutionoutput = $solutiontemplate->get();
		if(!$show_question_only)
		{
			// get page object output
			$solutionoutput = $this->getILIASPage($solutionoutput);
		}
		return $solutionoutput;
	}

	/**
	 * Returns the answer specific feedback for the question
	 * 
	 * @param integer $active_id Active ID of the user
	 * @param integer $pass Active pass
	 * @return string HTML Code with the answer specific feedback
	 * @access public
	 */
	public function getSpecificFeedbackOutput($userSolution): string
	{
		// By default no answer specific feedback is defined
		$output = "";
		return self::prepareTextareaOutput($output, TRUE);
	}
	
	
	/**
	* Sets the ILIAS tabs for this question type
	* called from ilObjTestGUI and ilObjQuestionPoolGUI
	*/
	public function setQuestionTabs(): void
	{
	    parent::setQuestionTabs();
	}
	
	/**
	 * Adds the question specific forms parts to a question property form gui.
	 */
	public function populateQuestionSpecificFormPart(ilPropertyFormGUI $form): ilPropertyFormGUI
	{
    	    $plugin = $this->object->getPlugin();
    	    
    	    // points
    	    $points = new ilNumberInputGUI($plugin->txt("points"), "points");
    	    $points->setSize(3);
    	    $points->setMinValue(0);
    	    $points->allowDecimals(1);
    	    $points->setRequired(true);
    	    $points->setValue($this->object->getPoints());
    	    
    	    $form->addItem($points);
    	    return $form;
	}
	
	/**
	 * Extracts the question specific values from the request and applies them
	 * to the data object.
	 */
	public function writeQuestionSpecificPostData(ilPropertyFormGUI $form): void
	{  
	    $this->object->setPoints($this->request_data_collector->float('points'));
	}
	
	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionQuestionPostVars(): array
	{
	    return [];
	}
	
	public function populateAnswerSpecificFormPart(\ilPropertyFormGUI $form): ilPropertyFormGUI
	{
	    return $form;
	}
	
	public function writeAnswerSpecificPostData(ilPropertyFormGUI $form): void
	{
	    #not needed for Infotext
	}
	
	/**
	 * Returns a list of postvars which will be suppressed in the form output when used in scoring adjustment.
	 * The form elements will be shown disabled, so the users see the usual form but can only edit the settings, which
	 * make sense in the given context.
	 *
	 * E.g. array('cloze_type', 'image_filename')
	 *
	 * @return string[]
	 */
	public function getAfterParticipationSuppressionAnswerPostVars(): array
	{
	    return [];
	}

	public function populateCorrectionsFormProperties(ilPropertyFormGUI $form): void
	{
	    $this->populateQuestionSpecificFormPart($form);
	}
	
	/**
	 * @param ilPropertyFormGUI $form
	 */
	public function saveCorrectionsFormProperties(ilPropertyFormGUI $form): void
	{
	    $this->object->setPoints((float) str_replace(',', '.', $form->getInput('points')));
	}
	
	public function prepareReprintableCorrectionsForm(ilPropertyFormGUI $form): void
	{
	    #not needed for Infotext
	}	
}
?>
