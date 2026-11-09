// URL base de la API.
// - Desarrollo: el cliente corre en localhost (p. ej. :5500) y la API en localhost:8000.
// - Producción (Render): API y cliente comparten dominio, por eso la base es "" (ruta relativa).
const isLocalDev =
  ["localhost", "127.0.0.1"].includes(location.hostname) &&
  location.port !== "8000";

export const API_BASE = isLocalDev ? "http://localhost:8000" : "";
