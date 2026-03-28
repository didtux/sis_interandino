-- =====================================================
-- Asociación Materia-Curso, Docente-Materia-Curso y Lista de Curso
-- Ejecutar: mysql -u root interandino < database/sql/create_curso_materia_docente_lista.sql
-- =====================================================

-- 1. Materias asignadas a un curso (ej: Matemáticas en 1ro Primaria A)
CREATE TABLE IF NOT EXISTS colegio_curso_materia (
    curmat_id INT(7) NOT NULL AUTO_INCREMENT,
    cur_codigo VARCHAR(20) NOT NULL,
    mat_codigo VARCHAR(14) NOT NULL,
    curmat_estado TINYINT(1) NOT NULL DEFAULT 1,
    curmat_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (curmat_id),
    UNIQUE KEY uk_curso_materia (cur_codigo, mat_codigo),
    KEY idx_cur (cur_codigo),
    KEY idx_mat (mat_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Docente asignado a una materia en un curso específico
CREATE TABLE IF NOT EXISTS colegio_curso_materia_docente (
    curmatdoc_id INT(7) NOT NULL AUTO_INCREMENT,
    cur_codigo VARCHAR(20) NOT NULL,
    mat_codigo VARCHAR(14) NOT NULL,
    doc_codigo VARCHAR(14) NOT NULL,
    curmatdoc_estado TINYINT(1) NOT NULL DEFAULT 1,
    curmatdoc_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (curmatdoc_id),
    UNIQUE KEY uk_curso_materia_docente (cur_codigo, mat_codigo),
    KEY idx_doc (doc_codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Número de lista de estudiante en su curso
CREATE TABLE IF NOT EXISTS colegio_lista_curso (
    lista_id INT(7) NOT NULL AUTO_INCREMENT,
    cur_codigo VARCHAR(20) NOT NULL,
    est_codigo VARCHAR(14) NOT NULL,
    lista_numero INT(3) NOT NULL,
    lista_gestion YEAR NOT NULL,
    lista_fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (lista_id),
    UNIQUE KEY uk_curso_est_gestion (cur_codigo, est_codigo, lista_gestion),
    UNIQUE KEY uk_curso_num_gestion (cur_codigo, lista_numero, lista_gestion),
    KEY idx_gestion (lista_gestion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
