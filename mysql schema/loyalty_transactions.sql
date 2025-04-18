CREATE TABLE wp_loyalty_transactions (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    user_id bigint(20) NOT NULL,
    order_id bigint(20),
    points int(11) NOT NULL,
    transaction_type varchar(20) NOT NULL,
    description text,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)