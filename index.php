<?php
session_start();
if (!isset($_SESSION['usuario_dni'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Portal de Certificados ISEM | MYS</title>
  <style>
    :root {
      --bg: #f1f5f9;
      --surface: #ffffff;
      --primary: #0f172a;
      --accent: #2563eb;
      --accent-hover: #1d4ed8;
      --text: #1e293b;
      --muted: #64748b;
      --border: #e2e8f0;
      --success-bg: #dcfce7;
      --success-text: #166534;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
    }

    header {
      background: var(--primary);
      color: #fff;
      padding: 1.5rem 1rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }
    .header-inner {
      max-width: 1140px;
      margin: 0 auto;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
    }
    header h1 { font-size: 1.35rem; font-weight: 700; letter-spacing: -0.01em; }
    header p { font-size: 0.875rem; color: #cbd5e1; margin-top: 0.2rem; }
    .badge-update {
      background: rgba(255,255,255,0.12);
      padding: 0.4rem 0.85rem;
      border-radius: 999px;
      font-size: 0.8rem;
      color: #e2e8f0;
    }

    main {
      max-width: 1140px;
      margin: 1.5rem auto;
      padding: 0 1rem 3rem;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .stat-card {
      background: var(--surface);
      padding: 1.1rem 1.25rem;
      border-radius: 10px;
      border: 1px solid var(--border);
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .stat-label { font-size: 0.8rem; color: var(--muted); text-transform: uppercase; font-weight: 600; }
    .stat-value { font-size: 1.65rem; font-weight: 700; color: var(--primary); margin-top: 0.25rem; }

    .search-panel {
      background: var(--surface);
      padding: 1.25rem;
      border-radius: 10px;
      border: 1px solid var(--border);
      margin-bottom: 1.5rem;
      display: grid;
      grid-template-columns: 2fr 1.5fr auto;
      gap: 0.85rem;
      align-items: end;
    }
    @media (max-width: 768px) {
      .search-panel { grid-template-columns: 1fr; }
    }

    .field label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      margin-bottom: 0.35rem;
      color: var(--text);
    }
    .field input, .field select {
      width: 100%;
      padding: 0.65rem 0.85rem;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.15s;
    }
    .field input:focus, .field select:focus { border-color: var(--accent); }

    .btn-clear {
      padding: 0.65rem 1.1rem;
      background: #e2e8f0;
      color: var(--text);
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.9rem;
    }
    .btn-clear:hover { background: #cbd5e1; }

    .worker-banner {
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 10px;
      padding: 1rem 1.25rem;
      margin-bottom: 1.25rem;
      display: none;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 0.75rem;
    }
    .worker-banner h2 { font-size: 1.05rem; color: #1e3a8a; }
    .worker-banner span { font-size: 0.88rem; color: #1d4ed8; font-weight: 600; }

    .table-wrap {
      background: var(--surface);
      border-radius: 10px;
      border: 1px solid var(--border);
      overflow-x: auto;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.9rem;
    }
    thead th {
      background: #f8fafc;
      padding: 0.85rem 1rem;
      font-size: 0.78rem;
      text-transform: uppercase;
      color: var(--muted);
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }
    tbody td {
      padding: 0.85rem 1rem;
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }
    tbody tr:hover { background: #f8fafc; }

    .dni-tag {
      font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
      background: #f1f5f9;
      padding: 0.2rem 0.5rem;
      border-radius: 6px;
      font-weight: 600;
      color: var(--primary);
    }
    .nota-badge {
      display: inline-block;
      background: var(--success-bg);
      color: var(--success-text);
      font-weight: 700;
      padding: 0.2rem 0.6rem;
      border-radius: 999px;
      font-size: 0.82rem;
    }
    .actions {
      display: flex;
      gap: 0.5rem;
      white-space: nowrap;
    }
    .btn-pdf {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.45rem 0.8rem;
      border-radius: 6px;
      text-decoration: none;
      font-size: 0.82rem;
      font-weight: 600;
      transition: background 0.15s;
    }
    .btn-view {
      background: var(--accent);
      color: #fff;
    }
    .btn-view:hover { background: var(--accent-hover); }
    .btn-dl {
      background: #f1f5f9;
      color: var(--text);
      border: 1px solid #cbd5e1;
    }
    .btn-dl:hover { background: #e2e8f0; }

    .empty-state {
      padding: 2.5rem 1rem;
      text-align: center;
      color: var(--muted);
    }
    .footer-note {
      margin-top: 1rem;
      font-size: 0.82rem;
      color: var(--muted);
      text-align: right;
    }
  </style>
</head>
<body>

<header>
  <div class="header-inner">
    <div>
      <h1>Consulta de Certificados ISEM</h1>
      <p>Mantenimiento y Supervisión S.A. &mdash; Verificación y descarga directa</p>
    </div>
    <div class="badge-update" id="badgeUpdate">Cargando base...</div>
    <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
  <div class="badge-update" id="badgeUpdate">Cargando base...</div>
      <span style="font-size:0.85rem; color:#cbd5e1;"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
      <a href="logout.php" style="background:#ef4444; color:#fff; padding:0.4rem 0.8rem; border-radius:6px; text-decoration:none; font-size:0.8rem; font-weight:600;">Salir</a>
    </div>
  </div>
</header>

<main>
  <section class="stats-grid">
    <div class="stat-card">
      <div class="stat-label">Certificados Registrados</div>
      <div class="stat-value" id="statCerts">-</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Trabajadores Habilitados</div>
      <div class="stat-value" id="statWorkers">-</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Resultados Mostrados</div>
      <div class="stat-value" id="statFiltered">-</div>
    </div>
  </section>

  <section class="search-panel">
    <div class="field">
      <label for="searchInput">Buscar por DNI o Apellidos y Nombres</label>
      <input type="text" id="searchInput" placeholder="Ej. 04207095 o ESPIRITU GONZALES..." autocomplete="off" />
    </div>
    <div class="field">
      <label for="courseFilter">Filtrar por Capacitación</label>
      <select id="courseFilter">
        <option value="">Todas las capacitaciones</option>
      </select>
    </div>
    <button class="btn-clear" id="btnClear" type="button">Limpiar filtros</button>
  </section>

  <div class="worker-banner" id="workerBanner">
    <div>
      <h2 id="workerName">TRABAJADOR</h2>
      <span id="workerDni">DNI: -</span>
    </div>
    <span id="workerCount">0 certificados</span>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>DNI</th>
          <th>Apellidos y Nombres</th>
          <th>Capacitación (Catálogo)</th>
          <th>Fecha</th>
          <th>Nota</th>
          <th>Certificado PDF</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        <tr><td colspan="6" class="empty-state">Cargando registros...</td></tr>
      </tbody>
    </table>
  </div>
  <div class="footer-note" id="limitNote"></div>
</main>

<script>
  const BASE_URL = "https://systemsft.com/CERTIFICADOS%20ISEM/";
  const MAX_ROWS_DEFAULT = 100;
  let allRecords = [];

  const searchInput = document.getElementById("searchInput");
  const courseFilter = document.getElementById("courseFilter");
  const btnClear = document.getElementById("btnClear");
  const tableBody = document.getElementById("tableBody");
  const workerBanner = document.getElementById("workerBanner");

  function buildPdfUrl(dni, filename) {
    return `${BASE_URL}${encodeURIComponent(dni)}/${encodeURIComponent(filename)}`;
  }

  function normalizeText(str) {
    return (str || "")
      .toString()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase();
  }

  function populateCourses(records) {
    const courses = [...new Set(records.map(r => r.t))].sort((a, b) => a.localeCompare(b));
    courses.forEach(course => {
      const opt = document.createElement("option");
      opt.value = course;
      opt.textContent = course;
      courseFilter.appendChild(opt);
    });
  }

  function renderTable() {
    const rawQuery = searchInput.value.trim();
    const query = normalizeText(rawQuery);
    const selectedCourse = courseFilter.value;

    const filtered = allRecords.filter(r => {
      const matchCourse = !selectedCourse || r.t === selectedCourse;
      if (!matchCourse) return false;
      if (!query) return true;
      return r.d.includes(query) || normalizeText(r.n).includes(query);
    });

    document.getElementById("statFiltered").textContent = filtered.length.toLocaleString("es-PE");

    // Mostrar ficha destacada si coincide un trabajador único
    const uniqueDnis = [...new Set(filtered.map(r => r.d))];
    if (rawQuery.length >= 4 && uniqueDnis.length === 1 && filtered.length > 0) {
      workerBanner.style.display = "flex";
      document.getElementById("workerName").textContent = filtered[0].n;
      document.getElementById("workerDni").textContent = `DNI: ${filtered[0].d}`;
      document.getElementById("workerCount").textContent = `${filtered.length} certificado(s) encontrado(s)`;
    } else {
      workerBanner.style.display = "none";
    }

    if (filtered.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="6" class="empty-state">No se encontraron certificados con los criterios ingresados.</td></tr>`;
      document.getElementById("limitNote").textContent = "";
      return;
    }

    const isSearching = query.length > 0 || selectedCourse !== "";
    const visibleRows = isSearching ? filtered : filtered.slice(0, MAX_ROWS_DEFAULT);

    tableBody.innerHTML = visibleRows.map(r => {
      const url = buildPdfUrl(r.d, r.a);
      return `
        <tr>
          <td><span class="dni-tag">${r.d}</span></td>
          <td><strong>${r.n}</strong></td>
          <td>${r.t}</td>
          <td style="white-space:nowrap;">${r.f}</td>
          <td><span class="nota-badge">${r.p}</span></td>
          <td>
            <div class="actions">
              <a class="btn-pdf btn-view" href="${url}" target="_blank" rel="noopener">Ver PDF</a>
              <a class="btn-pdf btn-dl" href="${url}" download="${r.a}">Descargar</a>
            </div>
          </td>
        </tr>
      `;
    }).join("");

    if (!isSearching && filtered.length > MAX_ROWS_DEFAULT) {
      document.getElementById("limitNote").textContent =
        `Mostrando los primeros ${MAX_ROWS_DEFAULT} de ${filtered.length.toLocaleString("es-PE")} certificados. Usa el buscador por DNI o nombre para filtrar toda la base.`;
    } else {
      document.getElementById("limitNote").textContent = "";
    }
  }

  async function init() {
    try {
      const res = await fetch(`datos_protegidos.php?v=${Date.now()}`);
      if (!res.ok) throw new Error("No se pudo cargar datos_isem.json");
      const data = await res.json();

      allRecords = data.registros || [];
      document.getElementById("badgeUpdate").textContent = `Actualizado: ${data.actualizado}`;
      document.getElementById("statCerts").textContent = (data.total_certificados || allRecords.length).toLocaleString("es-PE");
      document.getElementById("statWorkers").textContent = (data.total_trabajadores || 0).toLocaleString("es-PE");

      populateCourses(allRecords);
      renderTable();
    } catch (err) {
      tableBody.innerHTML = `<tr><td colspan="6" class="empty-state">Error al cargar la base de datos: ${err.message}</td></tr>`;
    }
  }

  searchInput.addEventListener("input", renderTable);
  courseFilter.addEventListener("change", renderTable);
  btnClear.addEventListener("click", () => {
    searchInput.value = "";
    courseFilter.value = "";
    renderTable();
  });

  init();
</script>
</body>
</html>