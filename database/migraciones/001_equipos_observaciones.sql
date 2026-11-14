-- Agrega el campo de observaciones/comentarios a los equipos.
-- Ejecutar UNA vez sobre una base que ya tenia datos (la base nueva lo recibe desde homi.sql).
ALTER TABLE equipos ADD COLUMN observaciones TEXT NULL AFTER clasificacion_invima;
