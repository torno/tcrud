-- Tabla de autores
CREATE TABLE IF NOT EXISTS authors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT NULL
);

-- Tabla de categorías
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    is_active TINYINT(1) DEFAULT 1
);

-- Tabla de libros
CREATE TABLE IF NOT EXISTS books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) DEFAULT NULL,
    author_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    published_date DATE DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    description TEXT DEFAULT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    status ENUM('draft','published','archived') DEFAULT 'draft',
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Tabla pivote N a N
CREATE TABLE IF NOT EXISTS book_authors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    author_id INT UNSIGNED NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
);

-- Datos de prueba
INSERT INTO authors (name, created_at) VALUES 
('Gabriel García Márquez', NOW()),
('Isabel Allende', NOW()),
('Jorge Luis Borges', NOW()),
('Julio Cortázar', NOW());

INSERT INTO categories (name) VALUES 
('Novela'), ('Ciencia Ficción'), ('Historia'), ('Biografía');

INSERT INTO books (title, isbn, author_id, category_id, published_date, price, description, status, created_at) VALUES
('Cien años de soledad', '978-0307474728', 1, 1, '1967-05-30', 25.99, 'Una obra maestra del realismo mágico.', 'published', NOW()),
('La casa de los espíritus', '978-1501117014', 2, 1, '1982-01-01', 19.99, 'La saga de la familia Trueba.', 'published', NOW()),
('Ficciones', '978-0802130303', 3, 2, '1944-01-01', 15.50, 'Cuentos fantásticos y filosóficos.', 'published', NOW()),
('Rayuela', '978-0394752848', 4, 1, '1963-06-28', 22.00, 'Una novela innovadora.', 'draft', NOW());

INSERT INTO book_authors (book_id, author_id, sort_order) VALUES
(1, 1, 0), (2, 2, 0), (3, 3, 0), (4, 4, 0), (1, 3, 1);