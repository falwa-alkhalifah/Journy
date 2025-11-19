-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 19, 2025 at 09:03 AM
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
  `ImageURL` varchar(255) DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`EventID`, `EventName`, `Category`, `City`, `Location`, `Description`, `StartDate`, `EndDate`, `AvailableTickets`, `ImageURL`, `Price`) VALUES
(1, 'Riyadh Music Festival', 'Music', 'Riyadh', 'Riyadh Boulevard', 'The largest music festival in the Middle East featuring local and international acts.', '2025-12-10', '2025-12-15', 500, 'image/MDLBEAST.jpg', '250.00'),
(2, 'Boulevard World', 'Entertainment', 'Riyadh', 'Riyadh Season Zone', 'A massive entertainment zone featuring diverse global experiences, massive rides, and international restaurants.', '2025-10-15', '2026-03-30', 9999, 'image/boulevardw.jpg', '500.00'),
(3, 'Saudi Tech Expo', 'Technology', 'Riyadh', 'RIEC', 'Discover the latest innovations and technologies transforming the Kingdom in this grand exhibition.', '2026-03-01', '2026-03-10', 1000, 'image/Exop.jpg', '100.00'),
(4, 'Formula 1 Grand Prix', 'Sports', 'Diriyah', 'Historic Diriyah', 'The thrilling electric street racing series set against the stunning backdrop of Diriyah.', '2025-11-20', '2025-11-21', 600, 'image/Formula.jpg', '200.00');

-- --------------------------------------------------------

--
-- Table structure for table `journeys`
--

CREATE TABLE `journeys` (
  `JourneyID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `JourneyName` varchar(150) NOT NULL,
  `Days` int(11) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `journeys`
--

INSERT INTO `journeys` (`JourneyID`, `UserID`, `JourneyName`, `Days`, `CreatedAt`) VALUES
(10, 1, 'Boulevard & Saudi Tech Journey', 1, '2025-11-18 08:56:10');

-- --------------------------------------------------------

--
-- Table structure for table `journey_days`
--

CREATE TABLE `journey_days` (
  `DayID` int(11) NOT NULL,
  `JourneyID` int(11) NOT NULL,
  `DayNumber` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `journey_days`
--

INSERT INTO `journey_days` (`DayID`, `JourneyID`, `DayNumber`) VALUES
(21, 10, 1);

-- --------------------------------------------------------

--
-- Table structure for table `journey_items`
--

CREATE TABLE `journey_items` (
  `ItemID` int(11) NOT NULL,
  `DayID` int(11) NOT NULL,
  `ReservationID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `journey_items`
--

INSERT INTO `journey_items` (`ItemID`, `DayID`, `ReservationID`) VALUES
(11, 21, 2),
(12, 21, 3);

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
  `LocallyOwned` tinyint(1) DEFAULT '0',
  `ImageURL` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `places`
--

INSERT INTO `places` (`PlaceID`, `EventID`, `Name`, `Type`, `City`, `DistanceFromEvent`, `PriceRange`, `Rating`, `LocallyOwned`, `ImageURL`) VALUES
(1, NULL, 'The Globe', 'Restaurant', 'Riyadh', '1.20', '$$$', '4.5', 1, 'image/TheGlobe.jpg'),
(2, NULL, 'Lazy Cat', 'Restaurant', 'Riyadh', '6.00', '$$', '4.3', 0, 'image/lazycat.webp'),
(3, NULL, 'Public', 'Restaurant', 'Riyadh', '3.90', '$$$', '4.8', 0, 'image/public.jpg'),
(4, NULL, 'Oulu', 'Restaurant', 'Riyadh', '2.00', '$$$', '4.5', 1, 'image/oulu.jpg'),
(5, NULL, 'Ritz-Carlton', 'Hotel', 'Riyadh', '3.50', '$$$', '4.8', 0, 'image/ritz.jpg'),
(6, NULL, 'Movenpick Hotel And Residences Riyadh', 'Hotel', 'Riyadh', '6.00', '$$', '4.3', 0, 'image/hotel.jpg'),
(7, NULL, 'Double Tree', 'Hotel', 'Riyadh', '1.90', '$$$', '4.5', 0, 'image/doubletree.webp'),
(8, NULL, 'Four Seasons', 'Hotel', 'Diriyah', '3.00', '$$', '4.2', 1, 'image/FourSeasons.webp');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `ReservationID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `EventID` int(11) DEFAULT NULL,
  `PlaceID` int(11) DEFAULT NULL,
  `NumberOfTickets` int(11) NOT NULL,
  `BookingDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` enum('Confirmed','Pending','Cancelled') DEFAULT 'Confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`ReservationID`, `UserID`, `EventID`, `PlaceID`, `NumberOfTickets`, `BookingDate`, `Status`) VALUES
(1, 1, 4, NULL, 1, '2025-11-18 06:47:48', 'Confirmed'),
(2, 1, 2, NULL, 1, '2025-11-18 06:48:12', 'Confirmed'),
(3, 1, 3, NULL, 1, '2025-11-18 06:48:14', 'Confirmed'),
(4, 1, 3, NULL, 1, '2025-11-18 06:48:15', 'Confirmed');

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
-- Indexes for table `journeys`
--
ALTER TABLE `journeys`
  ADD PRIMARY KEY (`JourneyID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `journey_days`
--
ALTER TABLE `journey_days`
  ADD PRIMARY KEY (`DayID`),
  ADD KEY `JourneyID` (`JourneyID`);

--
-- Indexes for table `journey_items`
--
ALTER TABLE `journey_items`
  ADD PRIMARY KEY (`ItemID`),
  ADD KEY `DayID` (`DayID`),
  ADD KEY `ReservationID` (`ReservationID`);

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
  ADD KEY `EventID` (`EventID`),
  ADD KEY `reservations_ibfk_3` (`PlaceID`);

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
-- AUTO_INCREMENT for table `journeys`
--
ALTER TABLE `journeys`
  MODIFY `JourneyID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `journey_days`
--
ALTER TABLE `journey_days`
  MODIFY `DayID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `journey_items`
--
ALTER TABLE `journey_items`
  MODIFY `ItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `places`
--
ALTER TABLE `places`
  MODIFY `PlaceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `ReservationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Constraints for table `journeys`
--
ALTER TABLE `journeys`
  ADD CONSTRAINT `journeys_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `journey_days`
--
ALTER TABLE `journey_days`
  ADD CONSTRAINT `journey_days_ibfk_1` FOREIGN KEY (`JourneyID`) REFERENCES `journeys` (`JourneyID`);

--
-- Constraints for table `journey_items`
--
ALTER TABLE `journey_items`
  ADD CONSTRAINT `journey_items_ibfk_1` FOREIGN KEY (`DayID`) REFERENCES `journey_days` (`DayID`),
  ADD CONSTRAINT `journey_items_ibfk_2` FOREIGN KEY (`ReservationID`) REFERENCES `reservations` (`ReservationID`);

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
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`EventID`) REFERENCES `events` (`EventID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`PlaceID`) REFERENCES `places` (`PlaceID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
