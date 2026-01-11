-- Migration: Add customer reviews/ratings system

-- Reviews/Ratings Table
CREATE TABLE IF NOT EXISTS product_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id),
    INDEX idx_product (product_id),
    INDEX idx_user (user_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE products
  ADD COLUMN IF NOT EXISTS rating DECIMAL(3,1) NULL AFTER price,
  ADD COLUMN IF NOT EXISTS discount TINYINT(3) NOT NULL DEFAULT 0 AFTER rating;

  UPDATE products
  SET discount_percentage = 15
  WHERE product_id = 1;


-- Sample seed data (optional - run after creating reviews):
-- INSERT INTO product_reviews (product_id, user_id, rating, review_text) VALUES
-- (1, 1, 5, 'Excellent product! Very satisfied.'),
-- (1, 2, 4, 'Good quality, shipping was fast.'),
-- (2, 1, 4, 'Great phone, camera is amazing.');

DELIMITER $$

DROP TRIGGER IF EXISTS update_product_rating_on_review_insert$$
CREATE TRIGGER update_product_rating_on_review_insert
AFTER INSERT ON product_reviews
FOR EACH ROW
BEGIN
    UPDATE products
    SET rating = (
        SELECT ROUND(AVG(rating), 1)
        FROM product_reviews
        WHERE product_id = NEW.product_id AND is_approved = TRUE
    )
    WHERE product_id = NEW.product_id;
END$$

DROP TRIGGER IF EXISTS update_product_rating_on_review_update$$
CREATE TRIGGER update_product_rating_on_review_update
AFTER UPDATE ON product_reviews
FOR EACH ROW
BEGIN
    UPDATE products
    SET rating = (
        SELECT ROUND(AVG(rating), 1)
        FROM product_reviews
        WHERE product_id = NEW.product_id AND is_approved = TRUE
    )
    WHERE product_id = NEW.product_id;
END$$

DROP TRIGGER IF EXISTS update_product_rating_on_review_delete$$
CREATE TRIGGER update_product_rating_on_review_delete
AFTER DELETE ON product_reviews
FOR EACH ROW
BEGIN
    UPDATE products
    SET rating = (
        SELECT ROUND(AVG(rating), 1)
        FROM product_reviews
        WHERE product_id = OLD.product_id AND is_approved = TRUE
    )
    WHERE product_id = OLD.product_id;
END$$

DELIMITER ;
