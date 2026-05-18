CREATE TABLE IF NOT EXISTS proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    bid_amount DECIMAL(12, 2) NOT NULL,
    cover_letter TEXT,
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn', 'shortlisted', 'archived', 'interviewing') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (freelancer_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    client_id INT NOT NULL,
    freelancer_id INT NOT NULL,
    proposal_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    contract_type ENUM('fixed', 'hourly') NOT NULL,
    status ENUM('active', 'completed', 'cancelled', 'disputed') DEFAULT 'active',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (client_id) REFERENCES users(id),
    FOREIGN KEY (freelancer_id) REFERENCES users(id),
    FOREIGN KEY (proposal_id) REFERENCES proposals(id)
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    job_id INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    FOREIGN KEY (job_id) REFERENCES jobs(id)
);
