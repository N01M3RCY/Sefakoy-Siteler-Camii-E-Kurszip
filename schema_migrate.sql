-- Migration: parent_id nullable (öğrenciyi doğrudan ekleyebilmek için)
ALTER TABLE students MODIFY COLUMN parent_id INT NULL;

-- Yaş alanı (doğrudan yaş girişi için)
ALTER TABLE students ADD COLUMN IF NOT EXISTS age INT NULL AFTER birth_date;

-- Dua tablosu
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

-- Ödev tablosu
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

-- Duyuru tablosu (bonus)
CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mosque_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mosque_id) REFERENCES mosques(id) ON DELETE CASCADE
) ENGINE=InnoDB;
