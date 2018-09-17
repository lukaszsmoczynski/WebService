<?php

include_once 'db.php';
include_once 'WSconstants.php';

final class WebService {

  public static $userID;

  private static function GetGUID() {
    if (function_exists('com_create_guid')) {
      return com_create_guid();
    } else {
      mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
      $charid = strtoupper(md5(uniqid(rand(), true)));
      $hyphen = chr(45); // "-"
      $uuid = chr(123)// "{"
              . substr($charid, 0, 8) . $hyphen
              . substr($charid, 8, 4) . $hyphen
              . substr($charid, 12, 4) . $hyphen
              . substr($charid, 16, 4) . $hyphen
              . substr($charid, 20, 12)
              . chr(125); // "}"
      return $uuid;
    }
  }

  public static function SaveLog($userID, $objectID, $objectType, $operationType, $comment) {
    global $connection;

    $log = '';
    switch ($operationType) {
      case OperationType::Delete:
        $log = 'Usunięcie: ' . $comment;
        break;
      case OperationType::Update:
        $log = 'Aktualizacja: ' . $comment;
        break;
      case OperationType::Insert:
        $log = 'Dodanie: ' . $comment;
        break;
    }

    $query = '';
    switch ($objectType) {
      case ObjectTypes::Note:
        $query = ' INSERT INTO LOGS(ID_USER, ID_OBJECT, OBJECT_TYPE, LOG) 
          VALUES(' . implode(', ', [$userID, $objectID, ObjectTypes::Note, quotedStr($log)]) . ') ';
        break;
      case ObjectTypes::StartClass:
        $query = ' INSERT INTO LOGS(ID_USER, ID_OBJECT, OBJECT_TYPE, LOG) 
          VALUES(' . implode(', ', [$userID, $objectID, ObjectTypes::StartClass, quotedStr($log)]) . ') ';
        break;
    }

    $connection->execQuery($query);
  }

  public static function ValidateToken($GUID) {
    global $connection;

    $query = $connection->execQuery(' SELECT * FROM TOKENS WHERE TOKEN = ' . quotedStr($GUID));

    if ($query->num_rows <= 0) {
      die;
    }

    $row = $query->fetch_assoc();
    WebService::$userID = $row['ID_USER'];
  }

  private static function SaveTokenToDB($userID, $GUID) {
    global $connection;
    return $connection->execQuery(' INSERT INTO TOKENS VALUES(' . $userID . ',' . quotedStr($GUID) . ')');
  }

  private static function RemoveTokenFromDB($GUID) {
    global $connection;
    return $connection->execQuery(' DELETE FROM TOKENS WHERE TOKEN = ' . quotedStr($GUID));
  }

  public static function Login($params) {
    global $connection;
    global $WebServiceConstants;

    $result = [];
    $query = $connection->execQuery(' SELECT ID,
                                        CASE WHEN EXISTS(SELECT * FROM PARENTS p WHERE p.ID_PARENT = u.ID) THEN 1 ELSE 0 END AS PARENT,
                                        CASE WHEN EXISTS(SELECT * FROM STUDENTS s WHERE s.ID_STUDENT = u.ID) THEN 1 ELSE 0 END AS STUDENT,
                                        CASE WHEN EXISTS(SELECT * FROM TEACHERS t WHERE t.ID_TEACHER = u.ID) THEN 1 ELSE 0 END AS TEACHER,
                                        CASE WHEN EXISTS(SELECT * FROM ADMINS a WHERE a.ID_ADMIN = u.ID) THEN 1 ELSE 0 END AS ADMIN
                                        FROM USERS u
                                        WHERE LOGIN = ' . quotedStr($params[$WebServiceConstants['Login']['Parameters']['LOGIN']]) .
            ' AND PASSWORD = ' . quotedStr($params[$WebServiceConstants['Login']['Parameters']['PASSWORD']]));

    if ($query->num_rows > 0) {
      $GUID = WebService::GetGUID();
      $row = $query->fetch_assoc();

      if (WebService::SaveTokenToDB($row['ID'], $GUID)) {
        $result = array($WebServiceConstants['Login']['Result']['RESULT'] => true,
            $WebServiceConstants['Login']['Result']['USER_TOKEN'] => $GUID,
            $WebServiceConstants['Login']['Result']['USER_ID'] => $row['ID'],
            $WebServiceConstants['Login']['Result']['IS_PARENT'] => ($row['PARENT'] > 0 ? true : false),
            $WebServiceConstants['Login']['Result']['IS_STUDENT'] => ($row['STUDENT'] > 0 ? true : false),
            $WebServiceConstants['Login']['Result']['IS_TEACHER'] => ($row['TEACHER'] > 0 ? true : false),
            $WebServiceConstants['Login']['Result']['IS_ADMIN'] => ($row['ADMIN'] > 0 ? true : false));
      } else {
        $result = array($WebServiceConstants['Login']['Result']['RESULT'] => false);
      }
    } else {
      $result = array($WebServiceConstants['Login']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function Logout($params) {
    global $WebServiceConstants;
    $result = [];

    if (WebService::RemoveTokenFromDB($params[$WebServiceConstants['TOKEN']])) {
      $result = array($WebServiceConstants['Logout']['Result']['RESULT'] => true);
    } else {
      $result = array($WebServiceConstants['Logout']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function GetChildrenOfParent($params) {
    global $connection;
    global $WebServiceConstants;
    $result = [];

    $query = $connection->execQuery(' SELECT ps.ID_STUDENT, u.NAME, u.SURNAME
                                        FROM PARENT_STUDENT ps
                                        JOIN USERS u ON u.ID = ps.ID_STUDENT
                                        WHERE ps.ID_PARENT = ' . $params[$WebServiceConstants['GetChildrenOfParent']['Parameters']['PARENT_ID']]);

    if ($query->num_rows > 0) {
      $result = array($WebServiceConstants['GetChildrenOfParent']['Result']['RESULT'] => true);
      $children = [];
      while ($row = $query->fetch_assoc()) {
        $children[] = array($WebServiceConstants['GetChildrenOfParent']['Result']['Children']['CHILDREN_ID'] => $row['ID_STUDENT'],
            $WebServiceConstants['GetChildrenOfParent']['Result']['Children']['CHILDREN_NAME'] => $row['NAME'],
            $WebServiceConstants['GetChildrenOfParent']['Result']['Children']['CHILDREN_SURNAME'] => $row['SURNAME']
        );
      }

      $result[$WebServiceConstants['GetChildrenOfParent']['Result']['CHILDREN']] = $children;
    } else {
      $result = array($WebServiceConstants['GetChildrenOfParent']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function GetStudentNotes($params) {
    global $connection;
    global $WebServiceConstants;
    $result = [];

    $query = $connection->execQuery(' SELECT n.ID NOTE_ID, u.NAME USER_NAME, u.SURNAME, n.VALUE, n.DATE, 
                                          n.REASON, c.NAME CLASS_NAME, c.ID CLASS_ID
                                        FROM NOTES n
                                        JOIN USERS u ON u.ID = n.ID_TEACHER
                                        LEFT JOIN CLASS_INSTANCES ci ON ci.ID = n.ID_CLASS_INSTANCE
                                        LEFT JOIN CLASSES c ON c.ID = ci.ID_CLASS
                                        WHERE n.ID_STUDENT = ' . $params[$WebServiceConstants['GetStudentNotes']['Parameters']['STUDENT_ID']] . '
                                        ORDER BY c.NAME ');

    if ($query->num_rows > 0) {
      $result = array($WebServiceConstants['GetStudentNotes']['Result']['RESULT'] => true);

      $studentNotes = array();
      $classId = -1;
      $className = '';
      $classNotes = array();

      while ($row = $query->fetch_assoc()) {
        if ($classId != $row['CLASS_ID']) {
          if ($classId != -1) {
            $studentNotes[] = array($WebServiceConstants['GetStudentNotes']['Result']['StudentNotes']['CLASS_NAME'] => $className,
                $WebServiceConstants['GetStudentNotes']['Result']['StudentNotes']['NOTES'] => $classNotes);
            $classNotes = array();
          }

          $classId = $row['CLASS_ID'];
          $className = ($row['CLASS_ID'] > 0 ? $row['CLASS_NAME'] : 'Bez przypisanych zajęć');
        }

        $classNotes[] = array($WebServiceConstants['GetStudentNotes']['Result']['StudentNotes']['Notes']['NOTE_ID'] => $row['NOTE_ID'],
            $WebServiceConstants['GetStudentNotes']['Result']['StudentNotes']['Notes']['NOTE_VALUE'] => $row['VALUE'],
            $WebServiceConstants['GetStudentNotes']['Result']['StudentNotes']['Notes']['GIVER'] => $row['USER_NAME'] . ' ' . $row['SURNAME'],
            $WebServiceConstants['GetStudentNotes']['Result']['StudentNotes']['Notes']['DATE_OF_OCCURENCE'] => $row['DATE'],
            $WebServiceConstants['GetStudentNotes']['Result']['StudentNotes']['Notes']['REASON'] => $row['REASON']);
      }

      if (count($classNotes) > 0) {
        $classId = $row['CLASS_ID'];
        $studentNotes[] = array($WebServiceConstants['GetStudentNotes']['Result']['StudentNotes']['CLASS_NAME'] => $className,
            $WebServiceConstants['GetStudentNotes']['Result']['StudentNotes']['NOTES'] => $classNotes);
      }

      $result[$WebServiceConstants['GetStudentNotes']['Result']['STUDENT_NOTES']] = $studentNotes;
    } else {
      $result = array($WebServiceConstants['GetStudentNotes']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function DeleteNote($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      WebService::SaveLog(WebService::$userID, $params[$WebServiceConstants['DeleteNote']['Parameters']['NOTE_ID']], ObjectTypes::Note, OperationType::Delete, $params[$WebServiceConstants['DeleteNote']['Parameters']['REASON']]);
      $connection->execQuery(' DELETE FROM NOTES 
                               WHERE ID = ' . $params[$WebServiceConstants['DeleteNote']['Parameters']['NOTE_ID']]);

      $connection->commit();
      $result = array($WebServiceConstants['DeleteNote']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['DeleteNote']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function GetSchedule($params) {
    global $connection;
    global $WebServiceConstants;
    $queryText = '';
    $subqueryText = '';

    $beginDate = $params[$WebServiceConstants['GetSchedule']['Parameters']['BEGIN_DATE']];
    $dayCount = $params[$WebServiceConstants['GetSchedule']['Parameters']['COUNT']];
    $userType = $params[$WebServiceConstants['GetSchedule']['Parameters']['USER_TYPE']];
    switch ($userType) {
      case 'TEACHER':
        $queryText = ' SELECT DISTINCT c.ID,
                         CONCAT_WS(\' \', COALESCE(CASE
                                           WHEN c.NAME <> s.NAME THEN \'(\' + s.NAME + \')\' 
                                           ELSE \'\'
                                         END, \'\'), c.NAME) AS NAME,
                         c.DAY_OF_OCCURENCE DAY_OF_OCCURENCE, c.BEGIN_TIME, c.END_TIME, c.BEGIN_DATE, c.END_DATE,
                         GROUP_CONCAT(DISTINCT CONCAT(\'"\', g.NAME, \'"\') ORDER BY g.NAME SEPARATOR \',\') GROUPS
                         FROM TEACHERS t 
                         JOIN CLASSES c ON c.ID_TEACHER = t.ID_TEACHER AND c.REPEATABLE
                           AND COALESCE(c.BEGIN_DATE, \'1900-01-01\') <= DATE_ADD(' . quotedStr($beginDate) . ', INTERVAL ' . $dayCount . ' DAY)
                           AND ' . quotedStr($beginDate) . ' <= COALESCE(c.END_DATE, \'9999-12-31\')
                         JOIN SUBJECTS s ON s.ID = c.ID_SUBJECT
                         JOIN CLASS_GROUP cg ON cg.ID_CLASS = c.ID
                         JOIN GROUPS g ON g.ID = cg.ID_GROUP
                         WHERE t.ID_TEACHER = ' . $params[$WebServiceConstants['GetSchedule']['Parameters']['USER_ID']] . '
                         GROUP BY cg.ID_CLASS
                         ORDER BY CASE 
                           WHEN c.DAY_OF_OCCURENCE < DAYOFWEEK(' . quotedStr($beginDate) . ') THEN c.DAY_OF_OCCURENCE + 8 - DAYOFWEEK(' . quotedStr($beginDate) . ')
                           ELSE c.DAY_OF_OCCURENCE - DAYOFWEEK(' . quotedStr($beginDate) . ')
                         END, c.BEGIN_TIME, c.ID ';
        $subqueryText = ' SELECT ID FROM CLASS_INSTANCES ci 
                          WHERE ci.ID_CLASS = ? AND CAST(ci.DATE_OF_OCCURENCE AS DATE) = ? AND CAST(ci.DATE_OF_OCCURENCE AS TIME) = ? ';
        break;
      case 'STUDENT':
        $queryText = ' SELECT DISTINCT c.ID,
                         CONCAT_WS(\' \', COALESCE(CASE
                                           WHEN c.NAME <> s.NAME THEN \'(\' + s.NAME + \')\' 
                                           ELSE \'\'
                                         END, \'\'), c.NAME) NAME,
                         c.DAY_OF_OCCURENCE DAY_OF_OCCURENCE, c.BEGIN_TIME, c.END_TIME, u.NAME USER_NAME, u.SURNAME, c.BEGIN_DATE, c.END_DATE, t.ID_TEACHER
                         FROM STUDENT_GROUP sg 
                         JOIN CLASS_GROUP cg ON cg.ID_GROUP = sg.ID_GROUP 
                         JOIN CLASSES c ON c.ID = cg.ID_CLASS AND c.REPEATABLE
                           AND COALESCE(c.BEGIN_DATE, \'1900-01-01\') <= DATE_ADD(' . quotedStr($beginDate) . ', INTERVAL ' . $dayCount . ' DAY)
                           AND ' . quotedStr($beginDate) . ' <= COALESCE(c.END_DATE, \'9999-12-31\')
                         JOIN SUBJECTS s ON s.ID = c.ID_SUBJECT
                         JOIN TEACHERS t ON t.ID_TEACHER = c.ID_TEACHER
                         JOIN USERS u ON u.ID = t.ID_TEACHER
                         WHERE sg.ID_STUDENT = ' . $params[$WebServiceConstants['GetSchedule']['Parameters']['USER_ID']] . '
                           ORDER BY CASE 
                             WHEN c.DAY_OF_OCCURENCE < DAYOFWEEK(' . quotedStr($beginDate) . ') THEN c.DAY_OF_OCCURENCE + 8 - DAYOFWEEK(' . quotedStr($beginDate) . ')
                             ELSE c.DAY_OF_OCCURENCE - DAYOFWEEK(' . quotedStr($beginDate) . ')
                           END, c.BEGIN_TIME, c.ID ';
        $subqueryText = ' SELECT PRESENT FROM ATTENDANCE a
                            JOIN CLASS_INSTANCES ci ON ci.ID = a.ID_CLASS_INSTANCE AND ci.ID_CLASS = ? 
                              AND CAST(ci.DATE_OF_OCCURENCE AS DATE) = ? AND CAST(ci.DATE_OF_OCCURENCE AS TIME) = ?
                            WHERE a.ID_STUDENT = ? ';
        break;
    }

    $query = $connection->execQuery($queryText);
    $subquery = $connection->prepareQuery($subqueryText);

    $result = array($WebServiceConstants['GetSchedule']['Result']['RESULT'] => ($query ? true : false));

    $classes = array();
    $i = 0;
    $schedule = [];

    $classesList = array();
    while ($row = $query->fetch_assoc()) {
      $classesList[] = array(
          'ID' => $row['ID'],
          'NAME' => $row['NAME'],
          'DAY_OF_OCCURENCE' => $row['DAY_OF_OCCURENCE'],
          'BEGIN_TIME' => $row['BEGIN_TIME'],
          'END_TIME' => $row['END_TIME'],
          'TEACHER' => ($row['ID_TEACHER'] != null ? $row['USER_NAME'] . ' ' . $row['SURNAME'] : null),
          'GROUPS' => str_getcsv($row['GROUPS']),
          'BEGIN_DATE' => $row['BEGIN_DATE'],
          'END_DATE' => $row['END_DATE']
      );
    }

    for ($i = 0; $i < $params[$WebServiceConstants['GetSchedule']['Parameters']['COUNT']]; $i++) {
      $date = strtotime($beginDate) + 24 * 60 * 60 * $i;
      $classes = array();
      foreach ($classesList as $class) {
        if ((date('w', $date) + 1 == $class['DAY_OF_OCCURENCE']) && ($date >= strtotime($class['BEGIN_DATE'])) && ($date <= strtotime($class['END_DATE']))) {
          $present = null;
          $instanceID = null;
          switch ($userType) {
            case 'TEACHER':
              $subquery->bind_param('iss', $class['ID'], date('Y-m-d', $date), $class['BEGIN_TIME']);
              $subquery->execute();

              if ($row = $subquery->get_result()->fetch_assoc()) {
                $instanceID = $row['ID'];
              }
              break;
            case 'STUDENT':
              $subquery->bind_param('issi', $class['ID'], date('Y-m-d', $date), $class['BEGIN_TIME'], $params[$WebServiceConstants['GetSchedule']['Parameters']['USER_ID']]);
              $subquery->execute();

              if ($row = $subquery->get_result()->fetch_assoc()) {
                $present = $row['PRESENT'] == 1;
              }
              break;
          }

          $classes[] = array(
              $WebServiceConstants['GetSchedule']['Result']['Schedule']['Classes']['CLASS_ID'] => $class['ID'],
              $WebServiceConstants['GetSchedule']['Result']['Schedule']['Classes']['CLASS_NAME'] => $class['NAME'],
              $WebServiceConstants['GetSchedule']['Result']['Schedule']['Classes']['BEGIN_TIME'] => $class['BEGIN_TIME'],
              $WebServiceConstants['GetSchedule']['Result']['Schedule']['Classes']['END_TIME'] => $class['END_TIME'],
              $WebServiceConstants['GetSchedule']['Result']['Schedule']['Classes']['TEACHER'] => $class['TEACHER'],
              $WebServiceConstants['GetSchedule']['Result']['Schedule']['Classes']['GROUPS'] => $class['GROUPS'],
              $WebServiceConstants['GetSchedule']['Result']['Schedule']['Classes']['PRESENT'] => $present,
              $WebServiceConstants['GetSchedule']['Result']['Schedule']['Classes']['INSTANCE_ID'] => $instanceID);
        }
      }

      $schedule[] = array($WebServiceConstants['GetSchedule']['Result']['Schedule']['DATE'] => date('Y-m-d', $date),
          $WebServiceConstants['GetSchedule']['Result']['Schedule']['CLASSES'] => $classes);
    }

    $result[$WebServiceConstants['GetSchedule']['Result']['SCHEDULE']] = $schedule;
    return $result;
  }

  public static function StartClass($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      WebService::SaveLog(WebService::$userID, $params[$WebServiceConstants['StartClass']['Parameters']['CLASS_ID']], ObjectTypes::StartClass, OperationType::Insert, $params[$WebServiceConstants['StartClass']['Parameters']['TOPIC']] . ' ' . $params[$WebServiceConstants['StartClass']['Parameters']['DATE']]);

      $connection->execQuery(' INSERT INTO CLASS_INSTANCES (DATE_OF_OCCURENCE, ID_TEACHER, TOPIC, ID_CLASS) '
              . ' VALUES (ADDTIME(' . quotedStr($params[$WebServiceConstants['StartClass']['Parameters']['DATE']]) . ', '
              . '  (SELECT c.BEGIN_TIME FROM CLASSES c WHERE c.ID = ' . $params[$WebServiceConstants['StartClass']['Parameters']['CLASS_ID']] . ')), '
              . ' ' . WebService::$userID . ', '
              . quotedStr($params[$WebServiceConstants['StartClass']['Parameters']['TOPIC']]) . ', '
              . ' ' . $params[$WebServiceConstants['StartClass']['Parameters']['CLASS_ID']] . ');');

      $query = $connection->execQuery(' SELECT LAST_INSERT_ID() ID; ');

      if ($query->num_rows <= 0) {
        $result = array($WebServiceConstants['StartClass']['Result']['RESULT'] => false);
      } else {
        $result = array($WebServiceConstants['StartClass']['Result']['RESULT'] => true,
            $WebServiceConstants['StartClass']['Result']['CLASS_INSTANCE_ID'] => $query->fetch_assoc()['ID']);
      }

      $connection->commit();
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['StartClass']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function EndClass($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
//      $connection->execQuery(' DELETE FROM NOTES 
//                                     WHERE ID = ' . $params[$WebServiceConstants['DeleteNote']['Parameters']['NOTE_ID']]);
//
//      $connection->execQuery(' DELETE FROM NOTES 
//                                     WHERE ID = -1 ');

      $connection->commit();
      $result = array($WebServiceConstants['StartClass']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['StartClass']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function GetStudentsOfClassInstance($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT DISTINCT u.ID, u.NAME, u.SURNAME '
              . ' FROM CLASS_INSTANCES ci '
              . ' JOIN CLASS_GROUP cg ON cg.ID_CLASS = ci.ID_CLASS '
              . ' JOIN STUDENT_GROUP sg ON sg.ID_GROUP = cg.ID_GROUP '
              . ' JOIN USERS u ON u.ID = sg.ID_STUDENT '
              . ' WHERE ci.ID = ' . $params[$WebServiceConstants['GetStudentsOfClassInstance']['Parameters']['CLASS_INSTANCE_ID']]);

      $students = array();
      while ($row = $query->fetch_assoc()) {
        $students[] = array(
            $WebServiceConstants['GetStudentsOfClassInstance']['Result']['Students']['ID'] => $row['ID'],
            $WebServiceConstants['GetStudentsOfClassInstance']['Result']['Students']['NAME'] => $row['NAME'],
            $WebServiceConstants['GetStudentsOfClassInstance']['Result']['Students']['SURNAME'] => $row['SURNAME']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetStudentsOfClassInstance']['Result']['RESULT'] => true,
          $WebServiceConstants['GetStudentsOfClassInstance']['Result']['STUDENTS'] => $students);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetStudentsOfClassInstance']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function SaveAttendance($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->prepareQuery(' REPLACE INTO ATTENDANCE(ID_CLASS_INSTANCE, ID_STUDENT, PRESENT) '
              . 'VALUES (' . $params[$WebServiceConstants['SaveAttendance']['Parameters']['CLASS_INSTANCE_ID']] . ', '
              . '?, ?) ');

      foreach ($params[$WebServiceConstants['SaveAttendance']['Parameters']['STUDENTS']] as $student) {
        $present = $student[$WebServiceConstants['SaveAttendance']['Parameters']['Students']['PRESENT']] ? 1 : 0;
        $query->bind_param('ii', $student[$WebServiceConstants['SaveAttendance']['Parameters']['Students']['ID']], $present);
        $query->execute();
      }

      $connection->commit();
      $result = array($WebServiceConstants['SaveAttendance']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveAttendance']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function IssueNote($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->prepareQuery(' INSERT INTO NOTES(VALUE, ID_STUDENT, ID_TEACHER, DATE, ID_CLASS_INSTANCE, REASON) '
              . 'VALUES ('
              . $params[$WebServiceConstants['IssueNote']['Parameters']['NOTE_VALUE']] . ', '
              . '?, ' .
              WebService::$userID . ', '
              . 'CURRENT_TIMESTAMP, '
              . $params[$WebServiceConstants['IssueNote']['Parameters']['CLASS_INSTANCE_ID']] . ', '
              . quotedStr($params[$WebServiceConstants['IssueNote']['Parameters']['REASON']]) . ') ');

      foreach ($params[$WebServiceConstants['IssueNote']['Parameters']['STUDENT_IDS']] as $studentID) {
        $query->bind_param('i', $studentID);
        $query->execute();
      }

      $connection->commit();
      $result = array($WebServiceConstants['IssueNote']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['IssueNote']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function GetUsers($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT ID, LOGIN, PASSWORD, NAME, SURNAME,
                                        CASE WHEN EXISTS(SELECT * FROM PARENTS p WHERE p.ID_PARENT = u.ID AND p.ACTIVE = 1) THEN 1 ELSE 0 END AS PARENT,
                                        CASE WHEN EXISTS(SELECT * FROM STUDENTS s WHERE s.ID_STUDENT = u.ID AND s.ACTIVE = 1) THEN 1 ELSE 0 END AS STUDENT,
                                        CASE WHEN EXISTS(SELECT * FROM TEACHERS t WHERE t.ID_TEACHER = u.ID AND t.ACTIVE = 1) THEN 1 ELSE 0 END AS TEACHER,
                                        CASE WHEN EXISTS(SELECT * FROM ADMINS a WHERE a.ID_ADMIN = u.ID AND a.ACTIVE = 1) THEN 1 ELSE 0 END AS ADMIN
                                        FROM USERS u 
                                        ORDER BY LOGIN ');

      $users = array();
      while ($row = $query->fetch_assoc()) {
        $users[] = array(
            $WebServiceConstants['GetUsers']['Result']['Users']['ID'] => $row['ID'],
            $WebServiceConstants['GetUsers']['Result']['Users']['LOGIN'] => $row['LOGIN'],
            $WebServiceConstants['GetUsers']['Result']['Users']['PASSWORD'] => $row['PASSWORD'],
            $WebServiceConstants['GetUsers']['Result']['Users']['NAME'] => $row['NAME'],
            $WebServiceConstants['GetUsers']['Result']['Users']['SURNAME'] => $row['SURNAME'],
            $WebServiceConstants['GetUsers']['Result']['Users']['TEACHER'] => $row['TEACHER'] == 1 ? true : false,
            $WebServiceConstants['GetUsers']['Result']['Users']['STUDENT'] => $row['STUDENT'] == 1 ? true : false,
            $WebServiceConstants['GetUsers']['Result']['Users']['PARENT'] => $row['PARENT'] == 1 ? true : false,
            $WebServiceConstants['GetUsers']['Result']['Users']['ADMIN'] => $row['ADMIN'] == 1 ? true : false
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetUsers']['Result']['RESULT'] => true,
          $WebServiceConstants['GetUsers']['Result']['USERS'] => $users);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetUsers']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function SaveUser($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    $id;
    try {
      if ($params[$WebServiceConstants['SaveUser']['Parameters']['ID']] != null) {
        $id = $params[$WebServiceConstants['SaveUser']['Parameters']['ID']];
        $connection->execQuery(' UPDATE USERS SET '
                . ' LOGIN = ' . quotedStr($params[$WebServiceConstants['SaveUser']['Parameters']['LOGIN']]) . ', '
                . (isset($params[$WebServiceConstants['SaveUser']['Parameters']['PASSWORD']]) 
                        ? ' PASSWORD = ' . quotedStr($params[$WebServiceConstants['SaveUser']['Parameters']['PASSWORD']]) . ', ' 
                        : '')
                . ' NAME = ' . quotedStr($params[$WebServiceConstants['SaveUser']['Parameters']['NAME']]) . ', '
                . ' SURNAME = ' . quotedStr($params[$WebServiceConstants['SaveUser']['Parameters']['SURNAME']]) . ' '
                . ' WHERE ID = ' . $params[$WebServiceConstants['SaveUser']['Parameters']['ID']]);
      } else {
        $connection->execQuery(' INSERT INTO USERS (LOGIN, ' 
                . (isset($params[$WebServiceConstants['SaveUser']['Parameters']['PASSWORD']]) 
                ? 'PASSWORD,' 
                : '') 
                . 'NAME, SURNAME, ACTIVE)'
                . ' VALUES ( '
                . quotedStr($params[$WebServiceConstants['SaveUser']['Parameters']['LOGIN']]) . ', '
                . (isset($params[$WebServiceConstants['SaveUser']['Parameters']['PASSWORD']]) 
                        ? quotedStr($params[$WebServiceConstants['SaveUser']['Parameters']['PASSWORD']]) . ', ' 
                        : '')
                . quotedStr($params[$WebServiceConstants['SaveUser']['Parameters']['NAME']]) . ', '
                . quotedStr($params[$WebServiceConstants['SaveUser']['Parameters']['SURNAME']]) . ', '
                . '1)');
        $query = $connection->execQuery(' SELECT LAST_INSERT_ID() ID; ');
        $id = $query->fetch_assoc()['ID'];
      }

      $connection->execQuery(' INSERT INTO TEACHERS(ID_TEACHER, ACTIVE) '
              . ' VALUES(' . $id . ', '
              . ' ' . ($params[$WebServiceConstants['SaveUser']['Parameters']['TEACHER']]?1:0) . ') '
              . ' ON DUPLICATE KEY UPDATE ID_TEACHER = ' . $id . ', '
              . ' ACTIVE = ' . ($params[$WebServiceConstants['SaveUser']['Parameters']['TEACHER']]?1:0) . ' ');

      $connection->execQuery(' INSERT INTO STUDENTS(ID_STUDENT, ACTIVE) '
              . ' VALUES(' . $id . ', '
              . ' ' . ($params[$WebServiceConstants['SaveUser']['Parameters']['STUDENT']]?1:0) . ') '
              . ' ON DUPLICATE KEY UPDATE ID_STUDENT = ' . $id . ', '
              . ' ACTIVE = ' . ($params[$WebServiceConstants['SaveUser']['Parameters']['STUDENT']]?1:0) . ' ');

      $connection->execQuery(' INSERT INTO PARENTS(ID_PARENT, ACTIVE) '
              . ' VALUES(' . $id . ', '
              . ' ' . ($params[$WebServiceConstants['SaveUser']['Parameters']['PARENT']]?1:0) . ') '
              . ' ON DUPLICATE KEY UPDATE ID_PARENT = ' . $id . ', '
              . ' ACTIVE = ' . ($params[$WebServiceConstants['SaveUser']['Parameters']['PARENT']]?1:0) . ' ');

      $connection->execQuery(' INSERT INTO ADMINS(ID_ADMIN, ACTIVE) '
              . ' VALUES(' . $id . ', '
              . ' ' . ($params[$WebServiceConstants['SaveUser']['Parameters']['ADMIN']]?1:0) . ') '
              . ' ON DUPLICATE KEY UPDATE ID_ADMIN = ' . $id . ', '
              . ' ACTIVE = ' . ($params[$WebServiceConstants['SaveUser']['Parameters']['ADMIN']]?1:0) . ' ');

      $connection->commit();
      $result = array($WebServiceConstants['SaveUser']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveUser']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function GetModules($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT ID, NAME
                                        FROM MODULES m
                                        ORDER BY NAME ');

      $modules = array();
      while ($row = $query->fetch_assoc()) {
        $modules[] = array(
            $WebServiceConstants['GetModules']['Result']['Modules']['ID'] => $row['ID'],
            $WebServiceConstants['GetModules']['Result']['Modules']['NAME'] => $row['NAME']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetModules']['Result']['RESULT'] => true,
          $WebServiceConstants['GetModules']['Result']['MODULES'] => $modules);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetModules']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function GetGroups($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT ID, NAME, CREATION_YEAR, ID_PARENT_GROUP
                                        FROM GROUPS m
                                        ORDER BY CREATION_YEAR, NAME ');

      $groups = array();
      while ($row = $query->fetch_assoc()) {
        $groups[] = array(
            $WebServiceConstants['GetGroups']['Result']['Groups']['ID'] => $row['ID'],
            $WebServiceConstants['GetGroups']['Result']['Groups']['NAME'] => $row['NAME'],
            $WebServiceConstants['GetGroups']['Result']['Groups']['CREATION_YEAR'] => $row['CREATION_YEAR'],
            $WebServiceConstants['GetGroups']['Result']['Groups']['PARENT_GROUP_ID'] => $row['ID_PARENT_GROUP']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetGroups']['Result']['RESULT'] => true,
          $WebServiceConstants['GetGroups']['Result']['GROUPS'] => $groups);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetGroups']['Result']['RESULT'] => false);
    }

    return $result;    
  }

  public static function SaveGroup($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $year = $params[$WebServiceConstants['SaveGroup']['Parameters']['CREATION_YEAR']];
      $parentGroupId = $params[$WebServiceConstants['SaveGroup']['Parameters']['PARENT_GROUP_ID']];
      if ($parentGroupId == null) {
        $parentGroupId = 'null';
      }
      
      if ($params[$WebServiceConstants['SaveGroup']['Parameters']['ID']] != null) {
        $connection->execQuery(' UPDATE GROUPS SET '
                . ' NAME = ' . quotedStr($params[$WebServiceConstants['SaveGroup']['Parameters']['NAME']]) . ', '
                . ' CREATION_YEAR = ' . $year . ', '
                . ' ID_PARENT_GROUP = ' . $parentGroupId . ' '
                . ' WHERE ID = ' . $params[$WebServiceConstants['SaveGroup']['Parameters']['ID']]);
      } else {
        $connection->execQuery(' INSERT INTO GROUPS (NAME, CREATION_YEAR, ID_PARENT_GROUP)'
                . ' VALUES ( '
                . quotedStr($params[$WebServiceConstants['SaveGroup']['Parameters']['NAME']]) . ', '
                . $year . ', '
                . $parentGroupId . ') ');
        $query = $connection->execQuery(' SELECT LAST_INSERT_ID() ID; ');
      }

      $connection->commit();
      $result = array($WebServiceConstants['SaveGroup']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveGroup']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function GetDocumentTypes($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT ID, NAME
                                        FROM DOCUMENT_TYPES dt
                                        ORDER BY NAME ');

      $documentTypes = array();
      while ($row = $query->fetch_assoc()) {
        $documentTypes[] = array(
            $WebServiceConstants['GetDocumentTypes']['Result']['DocumentTypes']['ID'] => $row['ID'],
            $WebServiceConstants['GetDocumentTypes']['Result']['DocumentTypes']['NAME'] => $row['NAME']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetDocumentTypes']['Result']['RESULT'] => true,
          $WebServiceConstants['GetDocumentTypes']['Result']['DOCUMENT_TYPES'] => $documentTypes);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetDocumentTypes']['Result']['RESULT'] => false);
    }

    return $result;  
  }

  public static function GetFormsOfEmployment($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT ID, NAME
                                        FROM FORMS_OF_EMPLOYMENT foe
                                        ORDER BY NAME ');

      $formsOfEmployment = array();
      while ($row = $query->fetch_assoc()) {
        $formsOfEmployment[] = array(
            $WebServiceConstants['GetFormsOfEmployment']['Result']['FormsOfEmployment']['ID'] => $row['ID'],
            $WebServiceConstants['GetFormsOfEmployment']['Result']['FormsOfEmployment']['NAME'] => $row['NAME']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetFormsOfEmployment']['Result']['RESULT'] => true,
          $WebServiceConstants['GetFormsOfEmployment']['Result']['FORMS_OF_EMPLOYMENT'] => $formsOfEmployment);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetFormsOfEmployment']['Result']['RESULT'] => false);
    }

    return $result;  
  }

  public static function GetSubjects($request) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT ID, NAME
                                        FROM SUBJECTS s
                                        ORDER BY NAME ');

      $subjects = array();
      while ($row = $query->fetch_assoc()) {
        $subjects[] = array(
            $WebServiceConstants['GetSubjects']['Result']['Subjects']['ID'] => $row['ID'],
            $WebServiceConstants['GetSubjects']['Result']['Subjects']['NAME'] => $row['NAME']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetSubjects']['Result']['RESULT'] => true,
          $WebServiceConstants['GetSubjects']['Result']['SUBJECTS'] => $subjects);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetSubjects']['Result']['RESULT'] => false);
    }

    return $result;  
  }

  public static function GetRelationTypes($request) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT ID, NAME
                                        FROM RELATION_TYPES rt
                                        ORDER BY NAME ');

      $relationTypes = array();
      while ($row = $query->fetch_assoc()) {
        $relationTypes[] = array(
            $WebServiceConstants['GetRelationTypes']['Result']['RelationTypes']['ID'] => $row['ID'],
            $WebServiceConstants['GetRelationTypes']['Result']['RelationTypes']['NAME'] => $row['NAME']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetRelationTypes']['Result']['RESULT'] => true,
          $WebServiceConstants['GetRelationTypes']['Result']['RELATION_TYPES'] => $relationTypes);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetRelationTypes']['Result']['RESULT'] => false);
    }

    return $result; 
  }

  public static function SaveSubject($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    $id;
    try {      
      if ($params[$WebServiceConstants['SaveSubject']['Parameters']['ID']] != null) {
        $id = $params[$WebServiceConstants['SaveSubject']['Parameters']['ID']];
        $connection->execQuery(' UPDATE SUBJECTS SET '
                . ' NAME = ' . quotedStr($params[$WebServiceConstants['SaveSubject']['Parameters']['NAME']]) . ' '
                . ' WHERE ID = ' . $params[$WebServiceConstants['SaveSubject']['Parameters']['ID']]);
      } else {
        $connection->execQuery(' INSERT INTO SUBJECTS (NAME)'
                . ' VALUES ( '
                . quotedStr($params[$WebServiceConstants['SaveSubject']['Parameters']['NAME']]) . ') ');
        $query = $connection->execQuery(' SELECT LAST_INSERT_ID() ID; ');
      }

      $connection->commit();
      $result = array($WebServiceConstants['SaveSubject']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveSubject']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function SaveRelationType($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    $id;
    try { 
      if ($params[$WebServiceConstants['SaveRelationType']['Parameters']['ID']] != null) {
        $id = $params[$WebServiceConstants['SaveRelationType']['Parameters']['ID']];
        $connection->execQuery(' UPDATE RELATION_TYPES SET '
                . ' NAME = ' . quotedStr($params[$WebServiceConstants['SaveRelationType']['Parameters']['NAME']]) . ' '
                . ' WHERE ID = ' . $params[$WebServiceConstants['SaveRelationType']['Parameters']['ID']]);
      } else {
        $connection->execQuery(' INSERT INTO RELATION_TYPES (NAME)'
                . ' VALUES ( '
                . quotedStr($params[$WebServiceConstants['SaveRelationType']['Parameters']['NAME']]) . ') ');
        $query = $connection->execQuery(' SELECT LAST_INSERT_ID() ID; ');
      }

      $connection->commit();
      $result = array($WebServiceConstants['SaveRelationType']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveRelationType']['Result']['RESULT'] => false);
    }

    return $result; 
  }

  public static function SaveDocumentType($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    $id;
    try { 
      if ($params[$WebServiceConstants['SaveDocumentType']['Parameters']['ID']] != null) {
        $id = $params[$WebServiceConstants['SaveDocumentType']['Parameters']['ID']];
        $connection->execQuery(' UPDATE DOCUMENT_TYPES SET '
                . ' NAME = ' . quotedStr($params[$WebServiceConstants['SaveDocumentType']['Parameters']['NAME']]) . ' '
                . ' WHERE ID = ' . $params[$WebServiceConstants['SaveDocumentType']['Parameters']['ID']]);
      } else {
        $connection->execQuery(' INSERT INTO DOCUMENT_TYPES (NAME)'
                . ' VALUES ( '
                . quotedStr($params[$WebServiceConstants['SaveDocumentType']['Parameters']['NAME']]) . ') ');
        $query = $connection->execQuery(' SELECT LAST_INSERT_ID() ID; ');
      }

      $connection->commit();
      $result = array($WebServiceConstants['SaveDocumentType']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveDocumentType']['Result']['RESULT'] => false);
    }

    return $result; 
  }

  public static function SaveFormOfEmployment($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    $id;
    try { 
      if ($params[$WebServiceConstants['SaveFormOfEmployment']['Parameters']['ID']] != null) {
        $id = $params[$WebServiceConstants['SaveFormOfEmployment']['Parameters']['ID']];
        $connection->execQuery(' UPDATE FORMS_OF_EMPLOYMENT SET '
                . ' NAME = ' . quotedStr($params[$WebServiceConstants['SaveFormOfEmployment']['Parameters']['NAME']]) . ' '
                . ' WHERE ID = ' . $params[$WebServiceConstants['SaveFormOfEmployment']['Parameters']['ID']]);
      } else {
        $connection->execQuery(' INSERT INTO FORMS_OF_EMPLOYMENT (NAME)'
                . ' VALUES ( '
                . quotedStr($params[$WebServiceConstants['SaveFormOfEmployment']['Parameters']['NAME']]) . ') ');
        $query = $connection->execQuery(' SELECT LAST_INSERT_ID() ID; ');
      }

      $connection->commit();
      $result = array($WebServiceConstants['SaveFormOfEmployment']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveFormOfEmployment']['Result']['RESULT'] => false);
    }

    return $result;     
  }

  public static function SaveClass($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try { 
      if (($id = $params[$WebServiceConstants['SaveClass']['Parameters']['ID']]) != null) {
//        $connection->execQuery(' UPDATE FORMS_OF_EMPLOYMENT SET '
//                . ' NAME = ' . quotedStr($params[$WebServiceConstants['SaveClass']['Parameters']['NAME']]) . ' '
//                . ' WHERE ID = ' . $params[$WebServiceConstants['SaveClass']['Parameters']['ID']]);
      } else {
        $connection->execQuery(' INSERT INTO CLASSES (NAME, DAY_OF_OCCURENCE, BEGIN_TIME, END_TIME, ID_TEACHER, ID_SUBJECT, BEGIN_DATE, END_DATE) '
                . ' VALUES ( '
                . quotedStr($params[$WebServiceConstants['SaveClass']['Parameters']['NAME']]) . ', '
                . $params[$WebServiceConstants['SaveClass']['Parameters']['DAY_OF_OCCURENCE']] . ', '
                . quotedStr($params[$WebServiceConstants['SaveClass']['Parameters']['BEGIN_TIME']]) . ', '
                . quotedStr($params[$WebServiceConstants['SaveClass']['Parameters']['END_TIME']]) . ', '
                . $params[$WebServiceConstants['SaveClass']['Parameters']['TEACHER_ID']] . ', '
                . $params[$WebServiceConstants['SaveClass']['Parameters']['SUBJECT_ID']] . ', '
                . quotedStr($params[$WebServiceConstants['SaveClass']['Parameters']['BEGIN_DATE']]) . ', '
                . quotedStr($params[$WebServiceConstants['SaveClass']['Parameters']['END_DATE']]) . ') ');
        
        if (isset($params[$WebServiceConstants['SaveClass']['Parameters']['GROUP_IDS']])) {
          $id = $connection->execQuery(' SELECT LAST_INSERT_ID() ID; ')->fetch_assoc()['ID'];
        
          $query = $connection->prepareQuery(' INSERT INTO CLASS_GROUP(ID_CLASS, ID_GROUP) VALUES(' . $id . ', ?) ');
          foreach ($params[$WebServiceConstants['SaveClass']['Parameters']['GROUP_IDS']] as $value) {
            $query->bind_param('i', $value);
            $query->execute();
          }
        }
      }

      $connection->commit();
      $result = array($WebServiceConstants['SaveFormOfEmployment']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveFormOfEmployment']['Result']['RESULT'] => false);
    }

    return $result;     
  }

  public static function GetStudents($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT u.ID, u.LOGIN, u.NAME, u.SURNAME, 
                                          s.DATE_OF_BIRTH, s.PLACE_OF_BIRTH, s.ID_DOCUMENT_TYPE, 
                                          dt.NAME TYPE_OF_DOCUMENT, s.DOCUMENT_NUMBER, 
                                          s.PESEL, s.COUNTRY, s.CITY, s.REGION, s.STREET, s.HOUSE_NUMBER, 
                                          s.FLAT_NUMBER, s.POSTAL_CODE, s.REASON_FOR_LEAVE
                                        FROM USERS u 
                                        JOIN STUDENTS s ON s.ID_STUDENT = u.ID AND s.ACTIVE = 1
                                        LEFT JOIN DOCUMENT_TYPES dt ON dt.ID = s.ID_DOCUMENT_TYPE
                                        ORDER BY u.LOGIN ');

      $students = array();
      while ($row = $query->fetch_assoc()) {
        $students[] = array(
            $WebServiceConstants['GetStudents']['Result']['Students']['ID'] => $row['ID'],
            $WebServiceConstants['GetStudents']['Result']['Students']['LOGIN'] => $row['LOGIN'],
            $WebServiceConstants['GetStudents']['Result']['Students']['NAME'] => $row['NAME'],
            $WebServiceConstants['GetStudents']['Result']['Students']['SURNAME'] => $row['SURNAME'],
            
            $WebServiceConstants['GetStudents']['Result']['Students']['DATE_OF_BIRTH'] => $row['DATE_OF_BIRTH'],
            $WebServiceConstants['GetStudents']['Result']['Students']['PLACE_OF_BIRTH'] => $row['PLACE_OF_BIRTH'],
            $WebServiceConstants['GetStudents']['Result']['Students']['ID_DOCUMENT_TYPE'] => $row['ID_DOCUMENT_TYPE'],
            $WebServiceConstants['GetStudents']['Result']['Students']['TYPE_OF_DOCUMENT'] => $row['TYPE_OF_DOCUMENT'],
            $WebServiceConstants['GetStudents']['Result']['Students']['DOCUMENT_NUMBER'] => $row['DOCUMENT_NUMBER'],
            $WebServiceConstants['GetStudents']['Result']['Students']['PESEL'] => $row['PESEL'],
            $WebServiceConstants['GetStudents']['Result']['Students']['COUNTRY'] => $row['COUNTRY'],
            $WebServiceConstants['GetStudents']['Result']['Students']['CITY'] => $row['CITY'],
            $WebServiceConstants['GetStudents']['Result']['Students']['REGION'] => $row['REGION'],
            $WebServiceConstants['GetStudents']['Result']['Students']['STREET'] => $row['STREET'],
            $WebServiceConstants['GetStudents']['Result']['Students']['HOUSE_NUMBER'] => $row['HOUSE_NUMBER'],
            $WebServiceConstants['GetStudents']['Result']['Students']['FLAT_NUMBER'] => $row['FLAT_NUMBER'],
            $WebServiceConstants['GetStudents']['Result']['Students']['POSTAL_CODE'] => $row['POSTAL_CODE'],
            $WebServiceConstants['GetStudents']['Result']['Students']['REASON_FOR_LEAVE'] => $row['REASON_FOR_LEAVE']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetStudents']['Result']['RESULT'] => true,
          $WebServiceConstants['GetStudents']['Result']['STUDENTS'] => $students);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetStudents']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function SaveStudent($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      if ($params[$WebServiceConstants['SaveStudent']['Parameters']['ID']] == null) {
        $result = array($WebServiceConstants['SaveStudent']['Result']['RESULT'] => false);
      } else {
        $connection->execQuery(' UPDATE STUDENTS SET '
                               . ' DATE_OF_BIRTH = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['DATE_OF_BIRTH']]) . ', '
                               . ' ID_DOCUMENT_TYPE = ' . $params[$WebServiceConstants['SaveStudent']['Parameters']['ID_DOCUMENT_TYPE']] . ', '
                               . ' DOCUMENT_NUMBER = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['DOCUMENT_NUMBER']]) . ', '
                               . ' PLACE_OF_BIRTH = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['PLACE_OF_BIRTH']]) . ', '
                               . ' PESEL = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['PESEL']]) . ', '
                               . ' COUNTRY = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['COUNTRY']]) . ', '
                               . ' CITY = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['CITY']]) . ', '
                               . ' REGION = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['REGION']]) . ', '
                               . ' STREET = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['STREET']]) . ', '
                               . ' HOUSE_NUMBER = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['HOUSE_NUMBER']]) . ', '
                               . ' FLAT_NUMBER = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['FLAT_NUMBER']]) . ', '
                               . ' POSTAL_CODE = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['POSTAL_CODE']]) . ', '
                               . ' REASON_FOR_LEAVE = ' . quotedStr($params[$WebServiceConstants['SaveStudent']['Parameters']['REASON_FOR_LEAVE']]) . ' '
                               . ' WHERE ID_STUDENT = ' . $params[$WebServiceConstants['SaveStudent']['Parameters']['ID']] . ' ');
        $connection->commit();
        $result = array($WebServiceConstants['SaveStudent']['Result']['RESULT'] => true);
      }
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveStudent']['Result']['RESULT'] => false);
    }

    return $result;    
  }

  public static function GetTeachers($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT u.ID, u.LOGIN, u.NAME, u.SURNAME, 
                                          t.HIRE_DATE, t.ID_FORM_OF_EMPLOYMENT, f.NAME FORM_OF_EMPLOYMENT
                                        FROM USERS u 
                                        JOIN TEACHERS t ON t.ID_TEACHER = u.ID AND t.ACTIVE = 1
                                        LEFT JOIN FORMS_OF_EMPLOYMENT f ON f.ID = t.ID_FORM_OF_EMPLOYMENT
                                        ORDER BY u.LOGIN ');

      $teachers = array();
      while ($row = $query->fetch_assoc()) {
        $teachers[] = array(
            $WebServiceConstants['GetTeachers']['Result']['Teachers']['ID'] => $row['ID'],
            $WebServiceConstants['GetTeachers']['Result']['Teachers']['LOGIN'] => $row['LOGIN'],
            $WebServiceConstants['GetTeachers']['Result']['Teachers']['NAME'] => $row['NAME'],
            $WebServiceConstants['GetTeachers']['Result']['Teachers']['SURNAME'] => $row['SURNAME'],
            
            $WebServiceConstants['GetTeachers']['Result']['Teachers']['HIRE_DATE'] => $row['HIRE_DATE'],
            $WebServiceConstants['GetTeachers']['Result']['Teachers']['ID_FORM_OF_EMPLOYMENT'] => $row['ID_FORM_OF_EMPLOYMENT'],
            $WebServiceConstants['GetTeachers']['Result']['Teachers']['FORM_OF_EMPLOYMENT'] => $row['FORM_OF_EMPLOYMENT']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetTeachers']['Result']['RESULT'] => true,
          $WebServiceConstants['GetTeachers']['Result']['TEACHERS'] => $teachers);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetTeachers']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function SaveTeacher($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      if ($params[$WebServiceConstants['SaveTeacher']['Parameters']['ID']] == null) {
        $result = array($WebServiceConstants['SaveTeacher']['Result']['RESULT'] => false);
      } else {
        $connection->execQuery(' UPDATE TEACHERS SET '
                               . ' HIRE_DATE = ' . quotedStr($params[$WebServiceConstants['SaveTeacher']['Parameters']['HIRE_DATE']]) . ', '
                               . ' ID_FORM_OF_EMPLOYMENT = ' . $params[$WebServiceConstants['SaveTeacher']['Parameters']['ID_FORM_OF_EMPLOYMENT']] . ' '
                               . ' WHERE ID_TEACHER = ' . $params[$WebServiceConstants['SaveTeacher']['Parameters']['ID']] . ' ');
        $connection->commit();
        $result = array($WebServiceConstants['SaveTeacher']['Result']['RESULT'] => true);
      }
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveTeacher']['Result']['RESULT'] => false);
    }

    return $result; 
  }

  public static function GetParents($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $queryText = ' SELECT u.ID, u.LOGIN, u.NAME, u.SURNAME,
                       p.COUNTRY, p.CITY, p.REGION, p.STREET, p.HOUSE_NUMBER, 
                       p.FLAT_NUMBER, p.POSTAL_CODE, p.E_MAIL,
                       GROUP_CONCAT(NUMBER SEPARATOR \';\') NUMBERS ';
      if ($params[$WebServiceConstants['GetParents']['Parameters']['FIND_STUDENTS_IDS']]) {
        $queryText .= ', GROUP_CONCAT(DISTINCT CONCAT_WS(\',\', ps.ID_STUDENT, ps.ID_RELATION_TYPE) SEPARATOR \';\') STUDENTS_RELATIONS ';
      }
      
      $queryText .= ' FROM USERS u 
                      JOIN PARENTS p ON p.ID_PARENT = u.ID AND p.ACTIVE = 1
                      LEFT JOIN PHONES ph ON ph.ID_PARENT = p.ID_PARENT ';
      if ($params[$WebServiceConstants['GetParents']['Parameters']['FIND_STUDENTS_IDS']]) {
        $queryText .= ' LEFT JOIN PARENT_STUDENT ps ON ps.ID_PARENT = p.ID_PARENT ';
      }
      
      $queryText .= ' GROUP BY u.ID
                      ORDER BY u.LOGIN ';
      
      $query = $connection->execQuery($queryText);

      $parents = array();
      while ($row = $query->fetch_assoc()) {
        $phones = explode(';', $row['NUMBERS']);
        
        $students = array();
        $studentsRelations = array();
        if ($params[$WebServiceConstants['GetParents']['Parameters']['FIND_STUDENTS_IDS']]) {
          $students = explode(';', $row['STUDENTS_RELATIONS']);
          foreach ($students as $value) {
            $explodeProduct = explode(',', $value);
            
            $studentsRelations[] = [$WebServiceConstants['GetParents']['Result']['Parents']['StudentsRelations']['STUDENT_ID'] => $explodeProduct[0],
                $WebServiceConstants['GetParents']['Result']['Parents']['StudentsRelations']['RELATION_TYPE_ID'] => $explodeProduct[1]];
          }
        }
        
        $parents[] = array(
            $WebServiceConstants['GetParents']['Result']['Parents']['ID'] => $row['ID'],
            $WebServiceConstants['GetParents']['Result']['Parents']['LOGIN'] => $row['LOGIN'],
            $WebServiceConstants['GetParents']['Result']['Parents']['NAME'] => $row['NAME'],
            $WebServiceConstants['GetParents']['Result']['Parents']['SURNAME'] => $row['SURNAME'],
            
            $WebServiceConstants['GetParents']['Result']['Parents']['COUNTRY'] => $row['COUNTRY'],
            $WebServiceConstants['GetParents']['Result']['Parents']['CITY'] => $row['CITY'],
            $WebServiceConstants['GetParents']['Result']['Parents']['REGION'] => $row['REGION'],
            $WebServiceConstants['GetParents']['Result']['Parents']['STREET'] => $row['STREET'],
            $WebServiceConstants['GetParents']['Result']['Parents']['HOUSE_NUMBER'] => $row['HOUSE_NUMBER'],
            $WebServiceConstants['GetParents']['Result']['Parents']['FLAT_NUMBER'] => $row['FLAT_NUMBER'],
            $WebServiceConstants['GetParents']['Result']['Parents']['POSTAL_CODE'] => $row['POSTAL_CODE'],
            $WebServiceConstants['GetParents']['Result']['Parents']['E_MAIL'] => $row['E_MAIL'],
            $WebServiceConstants['GetParents']['Result']['Parents']['PHONES'] => $phones,
            $WebServiceConstants['GetParents']['Result']['Parents']['STUDENTS_RELATIONS'] => $studentsRelations
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetParents']['Result']['RESULT'] => true,
          $WebServiceConstants['GetParents']['Result']['PARENTS'] => $parents);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetParents']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function SaveParent($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      if ($params[$WebServiceConstants['SaveParent']['Parameters']['ID']] == null) {
        $result = array($WebServiceConstants['SaveParent']['Result']['RESULT'] => false);
      } else {
        $connection->execQuery(' UPDATE PARENTS SET '
                               . ' COUNTRY = ' . quotedStr($params[$WebServiceConstants['SaveParent']['Parameters']['COUNTRY']]) . ', '
                               . ' CITY = ' . quotedStr($params[$WebServiceConstants['SaveParent']['Parameters']['CITY']]) . ', '
                               . ' REGION = ' . quotedStr($params[$WebServiceConstants['SaveParent']['Parameters']['REGION']]) . ', '
                               . ' STREET = ' . quotedStr($params[$WebServiceConstants['SaveParent']['Parameters']['STREET']]) . ', '
                               . ' HOUSE_NUMBER = ' . quotedStr($params[$WebServiceConstants['SaveParent']['Parameters']['HOUSE_NUMBER']]) . ', '
                               . ' FLAT_NUMBER = ' . quotedStr($params[$WebServiceConstants['SaveParent']['Parameters']['FLAT_NUMBER']]) . ', '
                               . ' POSTAL_CODE = ' . quotedStr($params[$WebServiceConstants['SaveParent']['Parameters']['POSTAL_CODE']]) . ', '
                               . ' E_MAIL = ' . quotedStr($params[$WebServiceConstants['SaveParent']['Parameters']['E_MAIL']]) . ' '
                               . ' WHERE ID_PARENT = ' . $params[$WebServiceConstants['SaveParent']['Parameters']['ID']] . ' ');
        
        $connection->execQuery('DELETE FROM PHONES WHERE ID_PARENT = ' . $params[$WebServiceConstants['SaveParent']['Parameters']['ID']]);
        foreach ($params[$WebServiceConstants['SaveParent']['Parameters']['PHONES']] as $phone) {
          $connection->execQuery('INSERT INTO PHONES(ID_PARENT, NUMBER) VALUES('
                  . $params[$WebServiceConstants['SaveParent']['Parameters']['ID']] . ', '
                  . quotedStr($phone) . ') ');
        }
        
        $connection->commit();
        $result = array($WebServiceConstants['SaveParent']['Result']['RESULT'] => true);
      }
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveParent']['Result']['RESULT'] => false);
    }

    return $result; 
  }

  public static function GetAdmins($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {
      $query = $connection->execQuery(' SELECT ID, LOGIN, NAME, SURNAME
                                        FROM USERS u 
                                        JOIN ADMINS a ON a.ID_ADMIN = u.ID AND a.ACTIVE = 1
                                        ORDER BY LOGIN ');

      $admins = array();
      while ($row = $query->fetch_assoc()) {
        $admins[] = array(
            $WebServiceConstants['GetAdmins']['Result']['Admins']['ID'] => $row['ID'],
            $WebServiceConstants['GetAdmins']['Result']['Admins']['LOGIN'] => $row['LOGIN'],
            $WebServiceConstants['GetAdmins']['Result']['Admins']['NAME'] => $row['NAME'],
            $WebServiceConstants['GetAdmins']['Result']['Admins']['SURNAME'] => $row['SURNAME']
        );
      }

      $connection->commit();
      $result = array($WebServiceConstants['GetAdmins']['Result']['RESULT'] => true,
          $WebServiceConstants['GetAdmins']['Result']['ADMINS'] => $admins);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['GetAdmins']['Result']['RESULT'] => false);
    }

    return $result;
  }

  public static function SaveParentChildren($params) {
    global $connection;
    global $WebServiceConstants;

    $connection->beginTransaction();
    try {      
      $result = array(0);
      $connection->execQuery(' DELETE FROM PARENT_STUDENT ');
      
      $query = $connection->prepareQuery(' INSERT INTO PARENT_STUDENT(ID_PARENT, ID_STUDENT, ID_RELATION_TYPE) '
                                       . ' VALUES (' . $params[$WebServiceConstants['SaveParentChildren']['Parameters']['PARENT_ID']] . ', ?, ?) ');
      
      foreach ($params[$WebServiceConstants['SaveParentChildren']['Parameters']['STUDENT_RELATION_TYPE']] as $child) {
        $query->bind_param('ii', 
                $child[$WebServiceConstants['SaveParentChildren']['Parameters']['StudentRelationType']['STUDENT_ID']], 
                $child[$WebServiceConstants['SaveParentChildren']['Parameters']['StudentRelationType']['RELATION_TYPE_ID']]);
        $query->execute();
      }

      $connection->commit();
      $result = array($WebServiceConstants['SaveParentChildren']['Result']['RESULT'] => true);
    } catch (Exception $e) {
      $connection->rollback();
      $result = array($WebServiceConstants['SaveParentChildren']['Result']['RESULT'] => false);
    }

    return $result;
  }
}

$request = json_decode(file_get_contents('php://input'), true);

//zapisz logi do pliku!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

if ($request[$WebServiceConstants['FUNCTION_NAME']] != $WebServiceConstants['Login']['FUNCTION_NAME']) {
  WebService::ValidateToken($request[$WebServiceConstants['TOKEN']]);
}

switch ($request[$WebServiceConstants['FUNCTION_NAME']]) {
  case $WebServiceConstants['Login']['FUNCTION_NAME']:
    $result = WebService::Login($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['Logout']['FUNCTION_NAME']:
    $result = WebService::Logout($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetChildrenOfParent']['FUNCTION_NAME']:
    $result = WebService::GetChildrenOfParent($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetStudentNotes']['FUNCTION_NAME']:
    $result = WebService::GetStudentNotes($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['DeleteNote']['FUNCTION_NAME']:
    $result = WebService::DeleteNote($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetSchedule']['FUNCTION_NAME']:
    $result = WebService::GetSchedule($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['StartClass']['FUNCTION_NAME']:
    $result = WebService::StartClass($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['EndClass']['FUNCTION_NAME']:
    $result = WebService::EndClass($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetStudentsOfClassInstance']['FUNCTION_NAME']:
    $result = WebService::GetStudentsOfClassInstance($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveAttendance']['FUNCTION_NAME']:
    $result = WebService::SaveAttendance($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['IssueNote']['FUNCTION_NAME']:
    $result = WebService::IssueNote($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetUsers']['FUNCTION_NAME']:
    $result = WebService::GetUsers($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveUser']['FUNCTION_NAME']:
    $result = WebService::SaveUser($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetModules']['FUNCTION_NAME']:
    $result = WebService::GetModules($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetGroups']['FUNCTION_NAME']:
    $result = WebService::GetGroups($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveGroup']['FUNCTION_NAME']:
    $result = WebService::SaveGroup($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetDocumentTypes']['FUNCTION_NAME']:
    $result = WebService::GetDocumentTypes($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetFormsOfEmployment']['FUNCTION_NAME']:
    $result = WebService::GetFormsOfEmployment($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetSubjects']['FUNCTION_NAME']:
    $result = WebService::GetSubjects($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetRelationTypes']['FUNCTION_NAME']:
    $result = WebService::GetRelationTypes($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveSubject']['FUNCTION_NAME']:
    $result = WebService::SaveSubject($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveRelationType']['FUNCTION_NAME']:
    $result = WebService::SaveRelationType($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveDocumentType']['FUNCTION_NAME']:
    $result = WebService::SaveDocumentType($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveFormOfEmployment']['FUNCTION_NAME']:
    $result = WebService::SaveFormOfEmployment($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveClass']['FUNCTION_NAME']:
    $result = WebService::SaveClass($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetStudents']['FUNCTION_NAME']:
    $result = WebService::GetStudents($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveStudent']['FUNCTION_NAME']:
    $result = WebService::SaveStudent($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetTeachers']['FUNCTION_NAME']:
    $result = WebService::GetTeachers($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveTeacher']['FUNCTION_NAME']:
    $result = WebService::SaveTeacher($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetParents']['FUNCTION_NAME']:
    $result = WebService::GetParents($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveParent']['FUNCTION_NAME']:
    $result = WebService::SaveParent($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['GetAdmins']['FUNCTION_NAME']:
    $result = WebService::GetAdmins($request[$WebServiceConstants['PARAMS']]);
    break;
  case $WebServiceConstants['SaveParentChildren']['FUNCTION_NAME']:
    $result = WebService::SaveParentChildren($request[$WebServiceConstants['PARAMS']]);
    break;
}

print_r(json_encode($result, JSON_PRETTY_PRINT));