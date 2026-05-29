# 🎵 Melodix - Spotify Clone

Melodix is a full-stack music streaming web application inspired by Spotify. The project allows users to browse songs, create playlists, manage their profiles, and enjoy an interactive music streaming experience. It also includes an admin panel for managing songs, playlists, users, and platform content.

---

## 🚀 Features

### User Features

* User Registration and Login
* Secure Authentication System
* Profile Management
* Upload Profile Picture
* Browse Music Library
* Search Songs
* Create and Manage Playlists
* Add Songs to Playlists
* Music Player Controls
* Responsive User Interface

### Admin Features

* Admin Dashboard
* Manage Users
* Add, Update, and Delete Songs
* Upload Song Files and Cover Images
* Manage Playlists
* View Platform Statistics
* Content Management System

---

## 🛠️ Technology Stack

### Frontend

* HTML5
* CSS3
* JavaScript
* Bootstrap

### Backend

* PHP

### Database

* MySQL

### Additional Tools

* PHPMailer (Email Services)
* Composer (Dependency Management)

---

## 📂 Project Structure

```text
spotifyClone/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── uploads/
│
├── controller/
│   ├── admin/
│   └── user/
│
├── views/
│
├── vendor/
│
├── db_config.php
├── mail.php
├── composer.json
└── index.php
```

---

## ⚙️ Installation Guide

### 1. Clone the Repository

```bash
git clone https://github.com/ChinnaparapuPavanKumar065/spotifyClone.git
```

### 2. Navigate to Project Folder

```bash
cd spotifyClone
```

### 3. Install Dependencies

```bash
composer install
```

### 4. Create Database

* Open phpMyAdmin.
* Create a new database.
* Import the provided SQL file:

```text
Dump20260523.sql
```

### 5. Configure Database

Update database credentials in:

```php
db_config.php
```

Example:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "spotify_clone";
```

### 6. Configure Email Settings

Update SMTP credentials in:

```php
mail.php
```

### 7. Start the Application

Place the project inside your XAMPP/WAMP/Laragon `htdocs` directory and run:

```text
http://localhost/spotifyClone
```

---

## 📸 Screenshots

### User Module

* Login Page
* Registration Page
* Home Dashboard
* Music Player
* Playlist Management
* Profile Management

### Admin Module

* Admin Dashboard
* Song Management
* Playlist Management
* User Management

---

## 🎯 Key Functionalities

* Authentication & Authorization
* Music Streaming Interface
* Playlist Creation & Management
* File Upload Management
* User Profile Management
* Admin Content Control
* Email Notification Integration

---

## 🔒 Security Features

* Session-Based Authentication
* Input Validation
* Secure File Upload Handling
* Protected Admin Routes
* Database Connectivity Management

---

## 📈 Future Enhancements

* AI-Based Music Recommendation System
* Real-Time Chat Between Users
* Collaborative Playlists
* Follow Artists and Other Users
* Music Download for Offline Listening
* Song Lyrics Integration
* Dark/Light Theme Toggle
* Mobile Application (Android & iOS)
* Social Media Sharing
* Listening History Analytics
* Personalized Daily Mixes
* Push Notifications for New Releases


---

## 👨‍💻 Developer

**Pavan Kumar**

GitHub: https://github.com/ChinnaparapuPavanKumar065

---

## 📄 License

This project is developed for educational, learning, and portfolio purposes only. Spotify is a trademark of Spotify AB. This project is not affiliated with or endorsed by Spotify.
