-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 17, 2025 at 07:00 PM
-- Server version: 5.7.24
-- PHP Version: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `journy_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `AdminID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Permissions` text,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `EventID` int(11) NOT NULL,
  `EventName` varchar(255) NOT NULL,
  `Category` varchar(100) DEFAULT NULL,
  `City` varchar(100) NOT NULL,
  `Location` text,
  `Description` text,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `AvailableTickets` int(11) NOT NULL DEFAULT '0',
  `ImageURL` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`EventID`, `EventName`, `Category`, `City`, `Location`, `Description`, `StartDate`, `EndDate`, `AvailableTickets`, `ImageURL`) VALUES
(1, 'Riyadh Music Festival', 'Music', 'Riyadh', 'Riyadh Boulevard', 'The largest music festival in the Middle East featuring local and international acts.', '2025-12-10', '2025-12-15', 500, 'image/MDLBEAST.jpg'),
(2, 'Boulevard World', 'Entertainment', 'Riyadh', 'Riyadh Season Zone', 'A massive entertainment zone featuring diverse global experiences, massive rides, and international restaurants.', '2025-10-15', '2026-03-30', 9999, 'image/boulevardw.jpg'),
(3, 'Saudi Tech Expo', 'Technology', 'Riyadh', 'RIEC', 'Discover the latest innovations and technologies transforming the Kingdom in this grand exhibition.', '2026-03-01', '2026-03-10', 1000, 'image/Exop.jpg'),
(4, 'Formula 1 Grand Prix', 'Sports', 'Diriyah', 'Historic Diriyah', 'The thrilling electric street racing series set against the stunning backdrop of Diriyah.', '2025-11-20', '2025-11-21', 600, 'image/Formula.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `places`
--

CREATE TABLE `places` (
  `PlaceID` int(11) NOT NULL,
  `EventID` int(11) DEFAULT NULL,
  `Name` varchar(255) NOT NULL,
  `Type` enum('Hotel','Restaurant','Other') NOT NULL,
  `City` varchar(100) NOT NULL,
  `DistanceFromEvent` decimal(5,2) DEFAULT NULL,
  `PriceRange` enum('$','$$','$$$') DEFAULT '$$',
  `Rating` decimal(2,1) DEFAULT '0.0',
  `ImageURL` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `places`
--

INSERT INTO `places` (`PlaceID`, `EventID`, `Name`, `Type`, `City`, `DistanceFromEvent`, `PriceRange`, `Rating`, `ImageURL`) VALUES
(1, NULL, 'The Globe', 'Restaurant', 'Riyadh', '1.20', '$$$', '4.5', 'image/TheGlobe.jpg'),
(2, NULL, 'Lazy Cat', 'Restaurant', 'Riyadh', '6.00', '$$', '4.3', 'image/lazycat.webp'),
(3, NULL, 'Public', 'Restaurant', 'Riyadh', '3.90', '$$$', '4.8', 'image/public.jpg'),
(4, NULL, 'Oulu', 'Restaurant', 'Riyadh', '2.00', '$$$', '4.5', 'image/oulu.jpg'),
(5, NULL, 'Ritz-Carlton', 'Hotel', 'Riyadh', '3.50', '$$$', '4.8', 'image/ritz.jpg'),
(6, NULL, 'Movenpick Hotel And Residences Riyadh', 'Hotel', 'Riyadh', '6.00', '$$', '4.3', 'image/hotel.jpg'),
(7, NULL, 'Double Tree', 'Hotel', 'Riyadh', '1.90', '$$$', '4.5', 'image/doubletree.webp'),
(8, NULL, 'Four Seasons', 'Hotel', 'Diriyah', '3.00', '$$', '4.2', 'image/FourSeasons.webp');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `ReservationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `EventID` int(11) NOT NULL,
  `NumberOfTickets` int(11) NOT NULL,
  `BookingDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` enum('Confirmed','Pending','Cancelled') DEFAULT 'Confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `FullName` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Role` enum('user','admin') DEFAULT 'user',
  `JoinDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `FullName`, `Email`, `Password`, `Role`, `JoinDate`) VALUES
(1, 'sana muneer', 'sana@gmail.com', '123456', 'user', '2025-11-17 16:33:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`EventID`);

--
-- Indexes for table `places`
--
ALTER TABLE `places`
  ADD PRIMARY KEY (`PlaceID`),
  ADD KEY `EventID` (`EventID`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`ReservationID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `EventID` (`EventID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `EventID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `places`
--
ALTER TABLE `places`
  MODIFY `PlaceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `ReservationID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `places`
--
ALTER TABLE `places`
  ADD CONSTRAINT `places_ibfk_1` FOREIGN KEY (`EventID`) REFERENCES `events` (`EventID`) ON DELETE SET NULL;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`EventID`) REFERENCES `events` (`EventID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
