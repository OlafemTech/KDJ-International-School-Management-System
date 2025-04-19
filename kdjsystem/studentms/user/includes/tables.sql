-- Table for subject combinations
CREATE TABLE IF NOT EXISTS `tblsubjectcombination` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ClassId` varchar(100) NOT NULL,
  `SubjectId` int(11) NOT NULL,
  `TeacherId` int(11) DEFAULT NULL,
  `Status` int(1) DEFAULT 1,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for subjects
CREATE TABLE IF NOT EXISTS `tblsubjects` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `SubjectName` varchar(100) NOT NULL,
  `SubjectCode` varchar(100) NOT NULL,
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for session and term
CREATE TABLE IF NOT EXISTS `tblsessionterm` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `CurrentSession` varchar(20) NOT NULL,
  `CurrentTerm` varchar(20) NOT NULL,
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for results
CREATE TABLE IF NOT EXISTS `tblresult` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `StudentId` int(11) NOT NULL,
  `ClassId` varchar(100) NOT NULL,
  `SubjectId` int(11) NOT NULL,
  `Marks` decimal(10,2) NOT NULL,
  `Term` varchar(20) NOT NULL,
  `Session` varchar(20) NOT NULL,
  `CreationDate` timestamp NULL DEFAULT current_timestamp(),
  `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default session and term
INSERT INTO `tblsessionterm` (`ID`, `CurrentSession`, `CurrentTerm`) VALUES
(1, '2024/2025', '1st Term')
ON DUPLICATE KEY UPDATE `CurrentSession` = '2024/2025', `CurrentTerm` = '1st Term';
