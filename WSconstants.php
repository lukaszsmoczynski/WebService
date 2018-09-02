<?php

class ObjectTypes {

  const Unknown = -1;
  const Note = 1;
  const StartClass = 2;

}

class OperationType {

  const Unknown = -1;
  const Insert = 1;
  const Update = 2;
  const Delete = 3;

}

$WebServiceConstants = array(
    'FUNCTION_NAME' => 'FUNCTION_NAME',
    'TOKEN' => 'TOKEN',
    'PARAMS' => 'PARAMS',
    'Login' => array(
        'FUNCTION_NAME' => 'Login',
        'Parameters' => array(
            'LOGIN' => 'LOGIN',
            'PASSWORD' => 'PASSWORD'
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'USER_TOKEN' => 'USER_TOKEN',
            'USER_ID' => 'USER_ID',
            'IS_STUDENT' => 'IS_STUDENT',
            'IS_TEACHER' => 'IS_TEACHER',
            'IS_PARENT' => 'IS_PARENT',
            'IS_ADMIN' => 'IS_ADMIN'
        )
    ),
    'Logout' => array(
        'FUNCTION_NAME' => 'Logout',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetChildrenOfParent' => array(
        'FUNCTION_NAME' => 'GetChildrenOfParent',
        'Parameters' => array(
            'PARENT_ID' => 'PARENT_ID'
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'CHILDREN' => 'CHILDREN',
            'Children' => array(
                'CHILDREN_ID' => 'CHILDREN_ID',
                'CHILDREN_NAME' => 'CHILDREN_NAME',
                'CHILDREN_SURNAME' => 'CHILDREN_SURNAME'
            )
        )
    ),
    'GetStudentNotes' => array(
        'FUNCTION_NAME' => 'GetStudentNotes',
        'Parameters' => array(
            'STUDENT_ID' => 'STUDENT_ID'
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'STUDENT_NOTES' => 'STUDENT_NOTES',
            'StudentNotes' => array(
                'CLASS_NAME' => 'CLASS_NAME',
                'NOTES' => 'NOTES',
                'Notes' => array(
                    'NOTE_ID' => 'NOTE_ID',
                    'NOTE_VALUE' => 'NOTE_VALUE',
                    'GIVER' => 'GIVER',
                    'DATE_OF_OCCURENCE' => 'DATE_OF_OCCURENCE',
                    'REASON' => 'REASON'
                )
            )
        )
    ),
    'DeleteNote' => array(
        'FUNCTION_NAME' => 'DeleteNote',
        'Parameters' => array(
            'NOTE_ID' => 'NOTE_ID',
            'REASON' => 'REASON'
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetSchedule' => array(
        'FUNCTION_NAME' => 'GetSchedule',
        'Parameters' => array(
            'USER_ID' => 'USER_ID',
            'USER_TYPE' => 'USER_TYPE',
            'BEGIN_DATE' => 'BEGIN_DATE',
            'COUNT' => 'COUNT'
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'SCHEDULE' => 'SCHEDULE',
            'Schedule' => array(
                'DATE' => 'DATE',
                'CLASSES' => 'CLASSES',
                'Classes' => array(
                    'CLASS_ID' => 'CLASS_ID',
                    'CLASS_NAME' => 'CLASS_NAME',
                    'BEGIN_TIME' => 'BEGIN_TIME',
                    'END_TIME' => 'END_TIME',
                    'TEACHER' => 'TEACHER',
                    'GROUPS' => 'GROUPS',
                    'PRESENT' => 'PRESENT',
                    'INSTANCE_ID' => 'INSTANCE_ID'
                )
            )
        )
    ),
    'StartClass' => array(
        'FUNCTION_NAME' => 'StartClass',
        'Parameters' => array(
            'CLASS_ID' => 'CLASS_ID',
            'TOPIC' => 'TOPIC',
            'DATE' => 'DATE'
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'CLASS_INSTANCE_ID' => 'CLASS_INSTANCE_ID'
        )
    ),
    'EndClass' => array(
        'FUNCTION_NAME' => 'EndClass',
        'Parameters' => array(
            'CLASS_INSTANCE_ID' => 'CLASS_INSTANCE_ID'
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetStudentsOfClassInstance' => array(
        'FUNCTION_NAME' => 'GetStudentsOfClassInstance',
        'Parameters' => array(
            'CLASS_INSTANCE_ID' => 'CLASS_INSTANCE_ID'
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'STUDENTS' => 'STUDENTS',
            'Students' => array(
                'ID' => 'ID',
                'NAME' => 'NAME',
                'SURNAME' => 'SURNAME'
            )
        )
    ),
    'SaveAttendance' => array(
        'FUNCTION_NAME' => 'SaveAttendance',
        'Parameters' => array(
            'CLASS_INSTANCE_ID' => 'CLASS_INSTANCE_ID',
            'STUDENTS' => 'STUDENTS',
            'Students' => array(
                'ID' => 'ID',
                'PRESENT' => 'PRESENT'
            )
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'IssueNote' => array(
        'FUNCTION_NAME' => 'IssueNote',
        'Parameters' => array(
            'CLASS_INSTANCE_ID' => 'CLASS_INSTANCE_ID',
            'NOTE_VALUE' => 'NOTE_VALUE',
            'REASON' => 'REASON',
            'STUDENT_IDS' => 'STUDENT_IDS'
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetUsers' => array(
        'FUNCTION_NAME' => 'GetUsers',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'USERS' => 'USERS',
            'Users' => array(
                'ID' => 'ID',
                'LOGIN' => 'LOGIN',
                'PASSWORD' => 'PASSWORD',
                'NAME' => 'NAME',
                'SURNAME' => 'SURNAME',
                'TEACHER' => 'TEACHER',
                'STUDENT' => 'STUDENT',
                'PARENT' => 'PARENT',
                'ADMIN' => 'ADMIN'
            )
        )
    ),
    'SaveUser' => array(
        'FUNCTION_NAME' => 'SaveUser',
        'Parameters' => array(
            'ID' => 'ID',
            'LOGIN' => 'LOGIN',
            'PASSWORD' => 'PASSWORD',
            'NAME' => 'NAME',
            'SURNAME' => 'SURNAME',
            'TEACHER' => 'TEACHER',
            'STUDENT' => 'STUDENT',
            'PARENT' => 'PARENT',
            'ADMIN' => 'ADMIN',
            'HIRE_DATE' => 'HIRE_DATE',
            'DATE_OF_BIRTH' => 'DATE_OF_BIRTH'
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetModules' => array(
        'FUNCTION_NAME' => 'GetModules',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'MODULES' => 'MODULES',
            'Modules' => array(
                'ID' => 'ID',
                'NAME' => 'NAME'
            )
        )
    ),
    'GetGroups' => array(
        'FUNCTION_NAME' => 'GetGroups',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'GROUPS' => 'GROUPS',
            'Groups' => array(
                'ID' => 'ID',
                'NAME' => 'NAME',
                'CREATION_YEAR' => 'CREATION_YEAR',
                'PARENT_GROUP_ID' => 'PARENT_GROUP_ID'
            )
        )
    ),
    'SaveGroup' => array(
        'FUNCTION_NAME' => 'SaveGroup',
        'Parameters' => array(
            "ID" => "ID",
            "NAME" => "NAME",
            "CREATION_YEAR" => "CREATION_YEAR",
            "PARENT_GROUP_ID" => "PARENT_GROUP_ID"
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetDocumentTypes' => array(
        'FUNCTION_NAME' => 'GetDocumentTypes',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'DOCUMENT_TYPES' => 'DOCUMENT_TYPES',
            'DocumentTypes' => array(
                'ID' => 'ID',
                'NAME' => 'NAME'
            )
        )
    ),
    'GetFormsOfEmployment' => array(
        'FUNCTION_NAME' => 'GetFormsOfEmployment',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'FORMS_OF_EMPLOYMENT' => 'FORMS_OF_EMPLOYMENT',
            'FormsOfEmployment' => array(
                'ID' => 'ID',
                'NAME' => 'NAME'
            )
        )
    ),
    'GetSubjects' => array(
        'FUNCTION_NAME' => 'GetSubjects',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'SUBJECTS' => 'SUBJECTS',
            'Subjects' => array(
                'ID' => 'ID',
                'NAME' => 'NAME'
            )
        )
    ),
    'GetRelationTypes' => array(
        'FUNCTION_NAME' => 'GetRelationTypes',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'RELATION_TYPES' => 'RELATION_TYPES',
            'RelationTypes' => array(
                'ID' => 'ID',
                'NAME' => 'NAME'
            )
        )
    ),
    'SaveSubject' => array(
        'FUNCTION_NAME' => 'SaveSubject',
        'Parameters' => array(
            "ID" => "ID",
            "NAME" => "NAME"
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'SaveRelationType' => array(
        'FUNCTION_NAME' => 'SaveRelationType',
        'Parameters' => array(
            "ID" => "ID",
            "NAME" => "NAME"
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'SaveDocumentType' => array(
        'FUNCTION_NAME' => 'SaveDocumentType',
        'Parameters' => array(
            "ID" => "ID",
            "NAME" => "NAME"
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'SaveFormOfEmployment' => array(
        'FUNCTION_NAME' => 'SaveFormOfEmployment',
        'Parameters' => array(
            "ID" => "ID",
            "NAME" => "NAME"
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'SaveClass' => array(
        'FUNCTION_NAME' => 'SaveClass',
        'Parameters' => array(
            "ID" => "ID",
            "NAME" => "NAME",
            "TEACHER_ID" => "TEACHER_ID",
            "SUBJECT_ID" => "SUBJECT_ID",
            "DAY_OF_OCCURENCE" => "DAY_OF_OCCURENCE",
            "GROUP_IDS" => "GROUP_IDS",
            "BEGIN_TIME" => "BEGIN_TIME",
            "END_TIME" => "END_TIME",
            "BEGIN_DATE" => "BEGIN_DATE",
            "END_DATE" => "END_DATE"
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetStudents' => array(
        'FUNCTION_NAME' => 'GetStudents',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'STUDENTS' => 'STUDENTS',
            'Students' => array(
                'ID' => 'ID',
                'LOGIN' => 'LOGIN',
                'NAME' => 'NAME',
                'SURNAME' => 'SURNAME',
                'DATE_OF_BIRTH' => 'DATE_OF_BIRTH',
                'PLACE_OF_BIRTH' => 'PLACE_OF_BIRTH',
                'ID_DOCUMENT_TYPE' => 'ID_DOCUMENT_TYPE',
                'TYPE_OF_DOCUMENT' => 'TYPE_OF_DOCUMENT',
                'DOCUMENT_NUMBER' => 'DOCUMENT_NUMBER',
                'PESEL' => 'PESEL',
                'COUNTRY' => 'COUNTRY',
                'CITY' => 'CITY',
                'REGION' => 'REGION',
                'STREET' => 'STREET',
                'HOUSE_NUMBER' => 'HOUSE_NUMBER',
                'FLAT_NUMBER' => 'FLAT_NUMBER',
                'POSTAL_CODE' => 'POSTAL_CODE',
                'REASON_FOR_LEAVE' => 'REASON_FOR_LEAVE'
            )
        )
    ),
    'SaveStudent' => array(
        'FUNCTION_NAME' => 'SaveStudent',
        'Parameters' => array(
            'ID' => 'ID',
            'DATE_OF_BIRTH' => 'DATE_OF_BIRTH',
            'PLACE_OF_BIRTH' => 'PLACE_OF_BIRTH',
            'ID_DOCUMENT_TYPE' => 'ID_DOCUMENT_TYPE',
            'DOCUMENT_NUMBER' => 'DOCUMENT_NUMBER',
            'PESEL' => 'PESEL',
            'COUNTRY' => 'COUNTRY',
            'CITY' => 'CITY',
            'REGION' => 'REGION',
            'STREET' => 'STREET',
            'HOUSE_NUMBER' => 'HOUSE_NUMBER',
            'FLAT_NUMBER' => 'FLAT_NUMBER',
            'POSTAL_CODE' => 'POSTAL_CODE',
            'REASON_FOR_LEAVE' => 'REASON_FOR_LEAVE'
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetTeachers' => array(
        'FUNCTION_NAME' => 'GetTeachers',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'TEACHERS' => 'TEACHERS',
            'Teachers' => array(
                'ID' => 'ID',
                'LOGIN' => 'LOGIN',
                'NAME' => 'NAME',
                'SURNAME' => 'SURNAME',
                'HIRE_DATE' => 'HIRE_DATE',
                'ID_FORM_OF_EMPLOYMENT' => 'ID_FORM_OF_EMPLOYMENT',
                'FORM_OF_EMPLOYMENT' => 'FORM_OF_EMPLOYMENT'
            )
        )
    ),
    'SaveTeacher' => array(
        'FUNCTION_NAME' => 'SaveTeacher',
        'Parameters' => array(
            'ID' => 'ID',
            'HIRE_DATE' => 'HIRE_DATE',
            'ID_FORM_OF_EMPLOYMENT' => 'ID_FORM_OF_EMPLOYMENT'
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetParents' => array(
        'FUNCTION_NAME' => 'GetParents',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'PARENTS' => 'PARENTS',
            'Parents' => array(
                'ID' => 'ID',
                'LOGIN' => 'LOGIN',
                'NAME' => 'NAME',
                'SURNAME' => 'SURNAME',
                'COUNTRY' => 'COUNTRY',
                'CITY' => 'CITY',
                'REGION' => 'REGION',
                'STREET' => 'STREET',
                'HOUSE_NUMBER' => 'HOUSE_NUMBER',
                'FLAT_NUMBER' => 'FLAT_NUMBER',
                'POSTAL_CODE' => 'POSTAL_CODE',
                'E_MAIL' => 'E_MAIL',
                'PHONES' => 'PHONES'
            )
        )
    ),
    'SaveParent' => array(
        'FUNCTION_NAME' => 'SaveParent',
        'Parameters' => array(
            'ID' => 'ID',
            'COUNTRY' => 'COUNTRY',
            'CITY' => 'CITY',
            'REGION' => 'REGION',
            'STREET' => 'STREET',
            'HOUSE_NUMBER' => 'HOUSE_NUMBER',
            'FLAT_NUMBER' => 'FLAT_NUMBER',
            'POSTAL_CODE' => 'POSTAL_CODE',
            'E_MAIL' => 'E_MAIL',
            'PHONES' => 'PHONES'
        ),
        'Result' => array(
            'RESULT' => 'RESULT'
        )
    ),
    'GetAdmins' => array(
        'FUNCTION_NAME' => 'GetAdmins',
        'Parameters' => array(
        ),
        'Result' => array(
            'RESULT' => 'RESULT',
            'ADMINS' => 'ADMINS',
            'Admins' => array(
                'ID' => 'ID',
                'LOGIN' => 'LOGIN',
                'NAME' => 'NAME',
                'SURNAME' => 'SURNAME'
            )
        )
    )
);
