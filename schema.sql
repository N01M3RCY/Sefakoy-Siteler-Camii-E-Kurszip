-- Cami Yönetim Sistemi - Veritabanı Şeması

CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mosques (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  address TEXT,
  district VARCHAR(100),
  city VARCHAR(100) DEFAULT 'İstanbul',
  imam_name VARCHAR(100),
  phone VARCHAR(20),
  email VARCHAR(100),
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  capacity INT DEFAULT 50,
  status ENUM('active','inactive','pending') DEFAULT 'pending',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS parents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  surname VARCHAR(100) NOT NULL,
  tc_no VARCHAR(11),
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(100),
  address TEXT,
  password VARCHAR(255) NULL,
  last_login TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mosque_id INT NOT NULL,
  parent_id INT NULL,
  name VARCHAR(100) NOT NULL,
  surname VARCHAR(100) NOT NULL,
  tc_no VARCHAR(11),
  birth_date DATE,
  age INT NULL,
  gender ENUM('male','female') NOT NULL,
  qr_code VARCHAR(20) UNIQUE NOT NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS duas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mosque_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  category ENUM('sabah','oglen','ikindi','aksam','yatsi','genel','ozel') DEFAULT 'genel',
  scheduled_date DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS homeworks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mosque_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  due_date DATE NULL,
  status ENUM('active','done') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS attendance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  mosque_id INT NOT NULL,
  scan_date DATE NOT NULL,
  scan_time TIME NOT NULL,
  notes VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Varsayılan admin (şifre: admin123)
INSERT IGNORE INTO admins (username, password, name, email)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sistem Yöneticisi', 'admin@cami.gov.tr');

-- Demo cami
INSERT IGNORE INTO mosques (id, name, district, city, imam_name, phone, username, password, status)
VALUES (1, 'Merkez Camii', 'Kadıköy', 'İstanbul', 'Ahmet Yılmaz', '0216 555 01 01',
        'merkez_camii', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');
