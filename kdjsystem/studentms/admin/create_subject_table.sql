-- Create tblsubject table
CREATE TABLE IF NOT EXISTS `tblsubject` (
    `ID` int(5) NOT NULL AUTO_INCREMENT,
    `SubjectName` varchar(100) NOT NULL,
    `SubjectCode` varchar(20) NOT NULL,
    `ClassID` int(5) NOT NULL,
    `TeacherID` int(5) DEFAULT NULL,
    `CreationDate` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `unique_subject_class` (`SubjectCode`, `ClassID`),
    CONSTRAINT `fk_subject_class` FOREIGN KEY (`ClassID`) REFERENCES `tblclass` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_subject_teacher` FOREIGN KEY (`TeacherID`) REFERENCES `tblteachers` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
