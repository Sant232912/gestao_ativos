CREATE TABLE centros_custo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    centro_custo VARCHAR(20) NOT NULL,
    filial_sap VARCHAR(20),
    grupo VARCHAR(10),
    bandeira VARCHAR(50),
    regional VARCHAR(100),
    razao_social VARCHAR(255),
    cnpj VARCHAR(20),
    ie VARCHAR(30),
    status VARCHAR(20)
);