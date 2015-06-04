# assInfotext
Infotext/Restudy-Questiontypeplugin for ILIAS 4.4.x/5.0.x

### Questiontype that allows inserting an additional element in your test, without any inputfields ###

It allows to insert a site in tests, which solely contains text/images/videos without any inputfields. Until now workarounds with other questiontypes had to be used to achieve this (e.g. the flash-Question with a renamed empty *.txt to *.swf). This has the disadvantage that those types can't have 0 points, therefore the "real" points always have to be recalculated manually after the test.

Points can be specified >= 0. This provides the flexibility to
* insert this element in a test without the need to recalculate the resulting points (Points == 0)
* grade a special test outside ILIAS manually after the test inside ILIAS (Points > 0) 

This may be usefull for:
* adding a message between questions, e.g. when a new section of questions starts
* separating media (audio/video) and questions
* insert learningmaterial ("restudy") during self assessment
* using paperbased worksheets or other analog tests along with your ILIAS-test and insert the points in ILIAS via manual grading afterwards

### Usage ###

Install the plugin to
* Customizing/global/plugins/Modules/TestQuestionPool/Questions

and activate it in the ILIAS-Admin-GUI. Activate manual correction.