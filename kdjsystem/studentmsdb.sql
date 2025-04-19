-- phpMyAdmin SQL Dump
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: `studentmsdb`
--

CREATE DATABASE IF NOT EXISTS `studentmsdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `studentmsdb`;

-- --------------------------------------------------------

--
-- Table structure for table `tbladmin`
--

DROP TABLE IF EXISTS `tbladmin`;
CREATE TABLE `tbladmin` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `AdminName` varchar(120) DEFAULT NULL,
  `UserName` varchar(120) DEFAULT NULL,
  `MobileNumber` varchar(20) DEFAULT NULL,
  `Email` varchar(200) DEFAULT NULL,
  `Password` varchar(200) DEFAULT NULL,
  `Avatar` varchar(255) DEFAULT 'default-avatar.png',
  `AdminRegdate` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Default admin login details
-- Username: admin
-- Password: admin
--
INSERT INTO `tbladmin` (`AdminName`, `UserName`, `MobileNumber`, `Email`, `Password`, `Avatar`, `AdminRegdate`) VALUES
('Administrator', 'admin', '1234567890', 'admin@mail.com', '21232f297a57a5a743894a0e4a801fc3', 'default-avatar.png', CURRENT_TIMESTAMP);

-- --------------------------------------------------------

--
-- Table structure for table `tblclass`
--

DROP TABLE IF EXISTS `tblclass`;
CREATE TABLE `tblclass` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(50) NOT NULL,
  `Level` varchar(10) NOT NULL,
  `Session` varchar(9) NOT NULL,
  `Term` varchar(20) NOT NULL,
  `CreationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_class_level_session_term` (`ClassName`, `Level`, `Session`, `Term`),
  CONSTRAINT `chk_class_name` CHECK (ClassName IN ('SS', 'JS', 'Basic', 'Nursery', 'PG')),
  CONSTRAINT `chk_level` CHECK (
    (ClassName = 'PG' AND Level = 'PG') OR 
    (ClassName != 'PG' AND Level IN ('1', '2', '3', '4', '5'))
  ),
  CONSTRAINT `chk_term` CHECK (Term IN ('1st Term', '2nd Term', '3rd Term')),
  CONSTRAINT `chk_session` CHECK (Session REGEXP '^[0-9]{4}/[0-9]{4}$'),
  CONSTRAINT `chk_session_years` CHECK (
    CAST(SUBSTRING_INDEX(Session, '/', -1) AS UNSIGNED) = 
    CAST(SUBSTRING_INDEX(Session, '/', 1) AS UNSIGNED) + 1
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblteacher`
--

DROP TABLE IF EXISTS `tblteacher`;
CREATE TABLE `tblteacher` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `FullName` varchar(200) NOT NULL,
  `Email` varchar(200) NOT NULL UNIQUE,
  `MobileNumber` varchar(20) NOT NULL,
  `Gender` varchar(10) NOT NULL,
  `MaritalStatus` varchar(20) NOT NULL,
  `DateOfBirth` date NOT NULL,
  `Address` text NOT NULL,
  `Qualification` enum('SSCE/Tech', 'NSCE/ND', 'HND/Bsc', 'Msc') NOT NULL,
  `TeacherId` varchar(100) NOT NULL UNIQUE,
  `JoiningDate` date NOT NULL,
  `UserName` varchar(120) NOT NULL UNIQUE,
  `Password` varchar(200) NOT NULL,
  `Image` varchar(200) DEFAULT 'default.jpg',
  `CV` varchar(200) DEFAULT NULL,
  `Certificate` varchar(200) DEFAULT NULL,
  `CreationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  CONSTRAINT `chk_teacher_gender` CHECK (Gender IN ('Male', 'Female')),
  CONSTRAINT `chk_marital_status` CHECK (MaritalStatus IN ('Single', 'Married', 'Divorced', 'Widowed')),
  CONSTRAINT `chk_mobile` CHECK (MobileNumber REGEXP '^[0-9]{11}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblstudent`
--

DROP TABLE IF EXISTS `tblstudent`;
CREATE TABLE `tblstudent` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `StudentName` varchar(200) NOT NULL,
  `StudentEmail` varchar(200) NOT NULL,
  `ClassID` int(11) NOT NULL,
  `Gender` varchar(10) NOT NULL,
  `DOB` date NOT NULL,
  `StudentId` varchar(100) NOT NULL,
  `FatherName` varchar(200) NOT NULL,
  `FatherOccupation` varchar(200) NOT NULL,
  `MotherName` varchar(200) NOT NULL,
  `MotherOccupation` varchar(200) NOT NULL,
  `ContactNumber` varchar(11) NOT NULL,
  `AlternateNumber` varchar(11) DEFAULT NULL,
  `Address` text NOT NULL,
  `Image` varchar(200) DEFAULT 'default.jpg',
  `UserName` varchar(120) NOT NULL,
  `Password` varchar(200) NOT NULL,
  `DateofAdmission` timestamp NOT NULL DEFAULT current_timestamp(),
  `Status` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `StudentEmail` (`StudentEmail`),
  UNIQUE KEY `StudentId` (`StudentId`),
  UNIQUE KEY `UserName` (`UserName`),
  CONSTRAINT `chk_student_gender` CHECK (Gender IN ('Male', 'Female')),
  CONSTRAINT `chk_contact_number` CHECK (ContactNumber REGEXP '^[0-9]{11}$'),
  CONSTRAINT `chk_alternate_number` CHECK (AlternateNumber IS NULL OR AlternateNumber REGEXP '^[0-9]{11}$'),
  CONSTRAINT `fk_student_class` FOREIGN KEY (`ClassID`) 
    REFERENCES `tblclass` (`ID`) 
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblsubjects`
--

DROP TABLE IF EXISTS `tblsubjects`;
CREATE TABLE `tblsubjects` (
  `ID` int(5) NOT NULL AUTO_INCREMENT,
  `SubjectName` varchar(100) NOT NULL,
  `SubjectCode` varchar(20) NOT NULL,
  `ClassID` int(5) NOT NULL,
  `TeacherID` int(5) DEFAULT NULL,
  `CreationDate` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_subject_class` (`SubjectCode`, `ClassID`),
  CONSTRAINT `fk_subject_class` FOREIGN KEY (`ClassID`) REFERENCES `tblclass` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_subject_teacher` FOREIGN KEY (`TeacherID`) REFERENCES `tblteacher` (`ID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `tblgrades`
--

DROP TABLE IF EXISTS `tblgrades`;
CREATE TABLE `tblgrades` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `StudentID` int(11) NOT NULL,
  `SubjectID` int(11) NOT NULL,
  `ClassID` int(11) NOT NULL,
  `Session` varchar(20) NOT NULL,
  `Term` varchar(20) NOT NULL,
  `CA1` decimal(5,2) NOT NULL DEFAULT 0.00,
  `CA2` decimal(5,2) NOT NULL DEFAULT 0.00,
  `Exam` decimal(5,2) NOT NULL DEFAULT 0.00,
  `TeacherComment` text DEFAULT NULL,
  `TeacherID` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`),
  UNIQUE KEY `unique_grade` (`StudentID`,`SubjectID`,`ClassID`,`Session`,`Term`),
  KEY `fk_student` (`StudentID`),
  KEY `fk_subject` (`SubjectID`),
  KEY `fk_class` (`ClassID`),
  KEY `fk_teacher` (`TeacherID`),
  CONSTRAINT `fk_student` FOREIGN KEY (`StudentID`) REFERENCES `tblstudent` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_subject` FOREIGN KEY (`SubjectID`) REFERENCES `tblsubjects` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_class` FOREIGN KEY (`ClassID`) REFERENCES `tblclass` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `fk_teacher` FOREIGN KEY (`TeacherID`) REFERENCES `tbladmin` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample classes for current session
INSERT INTO `tblclass` (`ClassName`, `Level`, `Session`, `Term`) VALUES
('SS', '1', '2024/2025', '1st Term'),
('SS', '2', '2024/2025', '1st Term'),
('SS', '3', '2024/2025', '1st Term'),
('JS', '1', '2024/2025', '1st Term'),
('JS', '2', '2024/2025', '1st Term'),
('JS', '3', '2024/2025', '1st Term'),
('Basic', '1', '2024/2025', '1st Term'),
('Basic', '2', '2024/2025', '1st Term'),
('Basic', '3', '2024/2025', '1st Term'),
('Basic', '4', '2024/2025', '1st Term'),
('Basic', '5', '2024/2025', '1st Term'),
('Nursery', '1', '2024/2025', '1st Term'),
('Nursery', '2', '2024/2025', '1st Term'),
('Nursery', '3', '2024/2025', '1st Term'),
('PG', 'PG', '2024/2025', '1st Term');

COMMIT;
