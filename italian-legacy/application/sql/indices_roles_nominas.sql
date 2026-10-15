-- Índices para acelerar Nominas::roles() sobre tbl_rol_semanal_nomina.
-- Probado en MySQL 8.0.46 con ~132 mil filas. Ejecutar fuera de horario pico:
-- el ALTER TABLE bloquea brevemente las escrituras sobre la tabla.
-- Cada sentencia falla si el índice ya existe (MySQL no admite IF NOT EXISTS aquí).

-- ============================================================
-- 1) APLICAR
-- ============================================================

-- Listados de nombres (getRolMatutino / getRolVespertino) y conteos por rol
-- (getTotalRol): igualdades en plaza, lugar y turno + rango de Fecha.
ALTER TABLE tbl_rol_semanal_nomina
  ADD INDEX idx_rol_plaza_lugar_turno_fecha (plaza_id, rol_cafeteria_id, turno_id, Fecha);

-- Conteos por día (getTotalDia): índice cubridor, no lee la tabla.
ALTER TABLE tbl_rol_semanal_nomina
  ADD INDEX idx_rol_plaza_fecha_dia (plaza_id, Fecha, dia_id);

-- ============================================================
-- 2) VERIFICAR
-- ============================================================

SELECT INDEX_NAME, SEQ_IN_INDEX, COLUMN_NAME, CARDINALITY, IS_VISIBLE
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'tbl_rol_semanal_nomina'
  AND INDEX_NAME IN ('idx_rol_plaza_lugar_turno_fecha', 'idx_rol_plaza_fecha_dia')
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- Plan esperado: key = idx_rol_plaza_lugar_turno_fecha (type range)
EXPLAIN
SELECT r.*, u.Nombre AS Nombre, u.Nombre_Rol AS Nombre_Rol
FROM tbl_rol_semanal_nomina r
JOIN tbl_usuarios_nomina u ON u.id = r.usuario_nomina_id
JOIN tbl_roles_nomina n ON n.id = u.rol_nomina_id
WHERE r.turno_id = 1 AND r.plaza_id = 1
  AND r.Fecha >= '2026-10-05' AND r.Fecha <= '2026-10-11'
  AND r.rol_cafeteria_id = 1;

-- Plan esperado: key = idx_rol_plaza_fecha_dia (type range, Using index)
EXPLAIN
SELECT COUNT(r.id) AS Total
FROM tbl_rol_semanal_nomina r
WHERE r.plaza_id = 1
  AND r.Fecha >= '2026-10-05' AND r.Fecha <= '2026-10-11'
  AND r.dia_id = 1;

-- ============================================================
-- 3) REVERTIR (no hay cambios de datos ni de código)
-- ============================================================
-- Nota: sin el primer índice los listados de nombres vuelven a ordenarse
-- por id en lugar de Fecha (no tenían ORDER BY).

-- DROP INDEX idx_rol_plaza_fecha_dia ON tbl_rol_semanal_nomina;
-- DROP INDEX idx_rol_plaza_lugar_turno_fecha ON tbl_rol_semanal_nomina;
