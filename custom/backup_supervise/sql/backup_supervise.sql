CREATE TABLE IF NOT EXISTS llx_backup_supervise_connection (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    ref varchar(128) NOT NULL,
    label varchar(255) NOT NULL,
    host varchar(255) NOT NULL,
    access_key varchar(255) NOT NULL,
    secret_key varchar(255) NOT NULL,
    service varchar(64) NOT NULL,
    billing_service varchar(64) DEFAULT NULL,
    note text,
    control_panel varchar(255) DEFAULT NULL,
    color varchar(16) DEFAULT '#2563eb',
    entity integer DEFAULT 1,
    tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    datec datetime DEFAULT NULL
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_backup_supervise_bucket (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    fk_connection integer NOT NULL,
    name varchar(255) NOT NULL,
    last_scan datetime DEFAULT NULL,
    size_bytes double DEFAULT 0,
    objects bigint DEFAULT 0,
    node varchar(255) DEFAULT NULL,
    last_updated datetime DEFAULT NULL,
    billing_month date DEFAULT NULL,
    fk_thirdparty integer DEFAULT NULL,
    fk_contract integer DEFAULT NULL,
    price_per_tb double DEFAULT 0,
    UNIQUE KEY uk_bucket_connection (fk_connection, name)
) ENGINE=innodb;

CREATE TABLE IF NOT EXISTS llx_backup_supervise_bucket_invoice (
    rowid integer AUTO_INCREMENT PRIMARY KEY,
    fk_bucket integer NOT NULL,
    fk_facture integer NOT NULL,
    fk_contract integer DEFAULT NULL,
    billing_month date DEFAULT NULL,
    UNIQUE KEY uk_bucket_invoice (fk_bucket, fk_facture)
) ENGINE=innodb;
