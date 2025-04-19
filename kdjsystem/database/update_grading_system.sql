-- Drop old grading tables
DROP TABLE IF EXISTS tblgrade_items;
DROP TABLE IF EXISTS tblgrade_categories;
DROP TABLE IF EXISTS tblgrades_old;
DROP TABLE IF EXISTS tblstudent_grades;
DROP TABLE IF EXISTS tblhomework_grades;

-- Create new grading table
CREATE TABLE IF NOT EXISTS `tblgrades` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `StudentID` int(11) NOT NULL,
  `SubjectID` int(11) NOT NULL,
  `CA1` decimal(5,2) DEFAULT NULL,
  `CA2` decimal(5,2) DEFAULT NULL,
  `TotalTest` decimal(5,2) GENERATED ALWAYS AS ((CA1 + CA2)) STORED,
  `Exam` decimal(5,2) DEFAULT NULL,
  `TotalScore` decimal(5,2) GENERATED ALWAYS AS (((TotalTest * 0.4) + (Exam * 0.6))) STORED,
  `TeacherComment` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_student_subject` (`StudentID`, `SubjectID`),
  FOREIGN KEY (`StudentID`) REFERENCES `tblstudents`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`SubjectID`) REFERENCES `tblsubjects`(`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `check_ca1` CHECK (`CA1` >= 0 AND `CA1` <= 20),
  CONSTRAINT `check_ca2` CHECK (`CA2` >= 0 AND `CA2` <= 20),
  CONSTRAINT `check_exam` CHECK (`Exam` >= 0 AND `Exam` <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
