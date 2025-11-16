<?php

/**
 * Infotext class for question type plugins
 *
 * @author	Christoph Jobst <iliasplugins.christoph.jobst@outlook.de>
 * @version	$Id:  $
 * @ingroup ModulesTestQuestionPool
 */

use ILIAS\Test\Logging\AdditionalInformationGenerator;

class assInfotext extends assQuestion implements ilObjQuestionScoringAdjustable
{
	/**
	 * @var ilassInfotextPlugin	The plugin object
	 */
    protected $plugin = null;


    /**
     * Constructor
     *
     * The constructor takes possible arguments and creates an instance of the question object.
     *
     * @param string $title A title string to describe the question
     * @param string $comment A comment string to describe the question
     * @param string $author A string containing the name of the questions author
     * @param integer $owner A numerical ID to identify the owner/creator
     * @param string $question Question text
     * @access public
     *
     * @see assQuestion:assQuestion()
     */
	function __construct( 
		$title = "",
		$comment = "",
		$author = "",
		$owner = -1,
		$question = ""
	)
	{
		// needed for excel export
		$this->getPlugin()->loadLanguageModule();

		parent::__construct($title, $comment, $author, $owner, $question);
	}

	/**
	 * Returns the question type of the question
	 *
	 * @return string The question type of the question
	 */
	public function getQuestionType() : string
	{
	    return "assInfotext";
	}

	/**
	 * Returns the names of the additional question data tables
	 *
	 * All tables must have a 'question_fi' column.
	 * Data from these tables will be deleted if a question is deleted
	 *
	 * @return mixed 	the name(s) of the additional tables (array or string)
	 */
	public function getAdditionalTableName(): string 
	{
	    return '';
	}
	
	/**
	 * Collects all texts in the question which could contain media objects
	 * which were created with the Rich Text Editor
	 */
	protected function getRTETextWithMediaObjects(): string
	{
	    $text = parent::getRTETextWithMediaObjects();
	    
	    // eventually add the content of question type specific text fields
	    // ..
	    
	    return (string) $text;
	}
	
	/**
	 * Get the plugin object
	 *
	 * @return object The plugin object
	 */
	public function getPlugin()
	{
	    global $DIC;
	    
	    if ($this->plugin == null)
	    {
	        /** @var ilComponentFactory $component_factory */
	        $component_factory = $DIC["component.factory"];
	        $this->plugin = $component_factory->getPlugin('infotext');
	    }
	    return $this->plugin;
	}
	
	/**
	 * Returns true, if the question is complete
	 *
	 * @return boolean True, if the question is complete for use, otherwise false
	 */
	public function isComplete(): bool
	{
	    // Please add here your own check for question completeness
	    // The parent function will always return false
	    if(!empty($this->title) && !empty($this->author) && !empty($this->question) && $this->getMaximumPoints() >= 0)
	    {
	        return true;
	    }
	    else
	    {
	        return false;
	    }
	}

	/**
	 * Saves a question object to a database
	 *
	 * @param	string		$original_id
	 * @access 	public
	 * @see assQuestion::saveToDb()
	 */
	function saveToDb($original_id = ''): void
	{
	    
	    // save the basic data (implemented in parent)
	    // a new question is created if the id is -1
	    // afterwards the new id is set
	    if ($original_id == '') {
	        $this->saveQuestionDataToDb();
	    } else {
	        $this->saveQuestionDataToDb($original_id);
	    }
	    
	    // Now you can save additional data
	    // ...
	    
	    // save stuff like suggested solutions
	    // update the question time stamp and completion status
	    parent::saveToDb();
	}

	/**
	 * Loads a question object from a database
	 * This has to be done here (assQuestion does not load the basic data)!
	 *
	 * @param integer $question_id A unique key which defines the question in the database
	 * @see assQuestion::loadFromDb()
	 */
	public function loadFromDb($question_id) : void
	{
        global $DIC;
        $ilDB = $DIC['ilDB'];	    
		// load the basic question data
		$result = $ilDB->query("SELECT qpl_questions.* FROM qpl_questions WHERE question_id = "
				. $ilDB->quote($question_id, 'integer'));
		
		if ($result->numRows() > 0) {
		    $data = $ilDB->fetchAssoc($result);
		    $this->setId($question_id);
		    $this->setObjId($data['obj_fi']);
		    $this->setOriginalId($data['original_id']);
		    $this->setOwner($data['owner']);
		    $this->setTitle((string) $data['title']);
		    $this->setAuthor($data['author']);
		    $this->setPoints($data['points']);
		    $this->setComment((string) $data['description']);
		    
		    $this->setQuestion(ilRTE::_replaceMediaObjectImageSrc((string) $data['question_text'], 1));
		    try {
		        $this->setLifecycle(ilAssQuestionLifecycle::getInstance($data['lifecycle']));
		    } catch (ilTestQuestionPoolInvalidArgumentException $e) {
		        $this->setLifecycle(ilAssQuestionLifecycle::getDraftInstance());
		    }
		    
		    // now you can load additional data
		    // ...
		    
		    try
		    {
		        $this->setAdditionalContentEditingMode($data['add_cont_edit_mode']);
		    }
		    catch(ilTestQuestionPoolException $e)
		    {
		    }
		}

		// loads additional stuff like suggested solutions
		parent::loadFromDb($question_id);
	}
	

	/**
	 * Duplicates a question
	 * This is used for copying a question to a test
	 *
	 * @access public
	 */
	public function duplicate(
	    bool $for_test = true,
	    string $title = '',
	    string $author = '',
	    int $owner = -1,
	    $test_obj_id = null
	    ): int {
	        if ($this->id <= 0) {
	            // The question has not been saved. It cannot be duplicated
	            return -1;
	        }
	        
	        $clone = clone $this;
	        $clone->id = -1;
	        
	        if ((int) $test_obj_id > 0) {
	            $clone->setObjId($test_obj_id);
	        }
	        
	        if ($title) {
	            $clone->setTitle($title);
	        }
	        if ($author) {
	            $clone->setAuthor($author);
	        }
	        if ($owner) {
	            $clone->setOwner($owner);
	        }
	        if ($for_test) {
	            $clone->saveToDb($this->id);
	        } else {
	            $clone->saveToDb();
	        }
	        
	        $clone->clonePageOfQuestion($this->getId());
	        $clone->cloneXHTMLMediaObjectsOfQuestion($this->getId());
	        
	        $clone = $this->cloneQuestionTypeSpecificProperties($clone);
	        
	        $clone->onDuplicate($this->getObjId(), $this->getId(), $clone->getObjId(), $clone->getId());
	        
	        return $clone->id;
	}

	/**
	 * Synchronize a question with its original
	 * You need to extend this function if a question has additional data that needs to be synchronized
	 * 
	 * @access public
	 */
	function syncWithOriginal() : void
	{
		parent::syncWithOriginal();
	}
	
	/**
	 * Get the submitted user input as a serializable value
	 *
	 * @return mixed user input (scalar, object or array)
	 */
	protected function getSolutionSubmit()
	{
	    return 0;
	}
	
	/**
	 * Get a stored solution for a user and test pass
	 * This is a wrapper to provide the same structure as getSolutionSubmit()
	 *
	 * @param int 	$active_id		active_id of hte user
	 * @param int	$pass			number of the test pass
	 * @param bool	$authorized		get the authorized solution
	 *
	 * @return	array	('value1' => string|null, 'value2' => float|null)
	 */
	public function getSolutionStored($active_id, $pass, $authorized = null)
	{
	    // no need for this qst
	    return 0;
	}
	
	/**
	 * Calculate the reached points for a submitted user input
	 *
	 * @return  float	reached points
	 */
	protected function calculateReachedPointsForSolution($solution)
	{
	    return 0;
	}
	
	/**
	 * Returns the points, a learner has reached answering the question
	 * The points are calculated from the given answers.
	 *
	 * @param integer $active_id 	The Id of the active learner
	 * @param ?integer $pass 	The Id of the test pass
	 * @param boolean $authorizedSolution
	 * @return float $points
	 * @access public
	 * @see  assQuestion::calculateReachedPoints()
	 */
	public function calculateReachedPoints(int $active_id, ?int $pass = null, bool $authorized_solution = true): float
	{
	    return 0;
	}
	
	/**
	 * Saves the learners input of the question to the database.
	 *
	 * @param integer $active_id 	Active id of the user
	 * @param integer $pass 		Test pass
	 * @param boolean $authorized	The solution is authorized
	 *
	 * @return 	boolean Indicates the save status (true if saved successful, false otherwise)
	 * @access 	public
	 * @see 	assQuestion::saveWorkingData()
	 */
    public function saveWorkingData(
        int $active_id,
        ?int $pass = null,
        bool $authorized = true
        ): bool {
            if ($pass === null) {
                $pass = ilObjTest::_getPass($active_id);
            }
            
            $answer = $this->getSolutionSubmit();
            $this->getProcessLocker()->executeUserSolutionUpdateLockOperation(
                function () use ($answer, $active_id, $pass, $authorized) {
                    $this->removeCurrentSolution($active_id, $pass, $authorized);
                    
                    if ($answer !== '') {
                        $this->saveCurrentSolution($active_id, $pass, $answer, null, $authorized);
                    }
                }
                );
            
            return true;
    }


	/**
	 * Reworks the allready saved working data if neccessary
	 *
	 * @access protected
	 * @param integer $active_id
	 * @param integer $pass
	 * @param boolean $obligationsAnswered
	 */
	protected function reworkWorkingData($active_id, $pass, $obligationsAnswered, $authorized)
	{
		// normally nothing needs to be reworked
	}

	/**
	* Returns the name of the answer table in the database
	*
	* @return array|string The answer table name
	* @access public
	*/
	function getAnswerTableName() : array|string
	{
		return "";
	}

	/**
	 * Creates an Excel worksheet for the detailed cumulated results of this question
	 *
	 * @access public
	 * @see assQuestion::setExportDetailsXLS()
	 */
    public function setExportDetailsXLSX(ilAssExcelFormatHelper $worksheet, int $startrow, int $col, int $active_id, int $pass) : int
	{
	    parent::setExportDetailsXLSX($worksheet, $startrow, $col, $active_id, $pass);
		return $startrow + 1;
	}
	
	// Generic log
	public function toLog(AdditionalInformationGenerator $additional_info) : array
	{
	    return [
	        AdditionalInformationGenerator::KEY_QUESTION_TYPE => (string) $this->getQuestionType(),
	        AdditionalInformationGenerator::KEY_QUESTION_TITLE => $this->getTitleForHTMLOutput(),
	        AdditionalInformationGenerator::KEY_QUESTION_TEXT => $this->formatSAQuestion($this->getQuestion()),
	        AdditionalInformationGenerator::KEY_QUESTION_REACHABLE_POINTS => $this->getPoints(),
	        AdditionalInformationGenerator::KEY_FEEDBACK => [
	           AdditionalInformationGenerator::KEY_QUESTION_FEEDBACK_ON_INCOMPLETE => $this->formatSAQuestion($this->feedbackOBJ->getGenericFeedbackTestPresentation($this->getId(), false)),
	           AdditionalInformationGenerator::KEY_QUESTION_FEEDBACK_ON_COMPLETE => $this->formatSAQuestion($this->feedbackOBJ->getGenericFeedbackTestPresentation($this->getId(), true))
	        ]
	    ];
	}
	
	// Infotext has no further log
	public function solutionValuesToLog(AdditionalInformationGenerator $additional_info, array $solution_values) : string
	{
	    return 'Infotext';
	}
	
	// Infotext has no further log
	public function solutionValuesToText(array $solution_values) : string
	{
	    return 'Infotext';
	}
	
	/**
	 * Saves a record to the question types additional data table.
	 *
	 * @return mixed
	 */
	public function saveAdditionalQuestionDataToDb()
	{
	    // nothing to save for Infotext
	    return 0;
	}
	
}
?>
