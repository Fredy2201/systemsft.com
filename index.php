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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background-color: #f1f5f9; }
    .navbar-custom { background-color: #0f172a; }
    .table > :not(caption) > * > * { vertical-align: middle; }
    .stat-card { cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08) !important; }
    .pdf-frame { width: 100%; height: 75vh; border: none; background: #1e293b; }
    .worker-avatar-sm { width: 36px; height: 36px; object-fit: cover; cursor: pointer; }
    .worker-avatar-lg { width: 76px; height: 76px; object-fit: cover; cursor: pointer; }
  </style>
</head>
<body>

  <!-- Navbar Superior -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
        <i class="bi bi-file-earmark-check-fill text-primary fs-4"></i>
        <div>
          <span class="d-block lh-1">Certificados ISEM</span>
          <small class="text-white-50 fw-normal" style="font-size: 0.72rem;">Mantenimiento y Supervisión S.A.</small>
        </div>
      </a>

      <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto flex-wrap">
        <span class="badge bg-secondary bg-opacity-25 text-light border border-secondary border-opacity-50 fw-normal py-2 px-3 rounded-pill d-none d-lg-inline-flex align-items-center gap-1" id="badgeUpdate">
          <i class="bi bi-clock-history"></i> Cargando base...
        </span>

        <div class="d-flex align-items-center gap-2 text-white small bg-white bg-opacity-10 px-3 py-1 rounded-pill">
          <i class="bi bi-person-circle text-info"></i>
          <span class="fw-medium text-truncate" style="max-width: 190px;"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
        </div>

        <a href="logout.php" class="btn btn-danger btn-sm fw-semibold rounded-pill px-3">
          <i class="bi bi-box-arrow-right me-1"></i>Salir
        </a>
      </div>
    </div>
  </nav>

  <main class="container py-4">

    <!-- Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-primary border-4 stat-card" onclick="filtrarPorTarjeta('')">
          <div class="card-body d-flex align-items-center justify-content-between p-3">
            <div>
              <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem;">Certificados Activos</div>
              <div class="fs-3 fw-bold text-dark mt-1" id="statTotal">-</div>
              <small class="text-muted" id="statWorkers">- trabajadores</small>
            </div>
            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 p-md-3">
              <i class="bi bi-award-fill fs-4"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-success border-4 stat-card" onclick="filtrarPorTarjeta('VIGENTE')">
          <div class="card-body d-flex align-items-center justify-content-between p-3">
            <div>
              <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem;">Vigentes</div>
              <div class="fs-3 fw-bold text-success mt-1" id="statVigentes">-</div>
              <small class="text-muted">Más de 30 días restantes</small>
            </div>
            <div class="bg-success bg-opacity-10 text-success rounded-3 p-2 p-md-3">
              <i class="bi bi-check-circle-fill fs-4"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-warning border-4 stat-card" onclick="filtrarPorTarjeta('POR_VENCER')">
          <div class="card-body d-flex align-items-center justify-content-between p-3">
            <div>
              <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem;">Por Vencer (≤30d)</div>
              <div class="fs-3 fw-bold text-warning-emphasis mt-1" id="statPorVencer">-</div>
              <small class="text-muted">Requieren revalidación</small>
            </div>
            <div class="bg-warning bg-opacity-10 text-warning-emphasis rounded-3 p-2 p-md-3">
              <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-danger border-4 stat-card" onclick="filtrarPorTarjeta('VENCIDO')">
          <div class="card-body d-flex align-items-center justify-content-between p-3">
            <div>
              <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem;">Vencidos</div>
              <div class="fs-3 fw-bold text-danger mt-1" id="statVencidos">-</div>
              <small class="text-muted">Plazo expirado</small>
            </div>
            <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-2 p-md-3">
              <i class="bi bi-x-octagon-fill fs-4"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel de Búsqueda y Filtros -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
      <div class="card-body p-3 p-md-4">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-lg-5">
            <label for="searchInput" class="form-label fw-semibold small text-secondary">
              Buscar por DNI o Apellidos y Nombres
            </label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
              <input type="text" class="form-control" id="searchInput" placeholder="Ej. 04207095 o ESPIRITU..." autocomplete="off" />
            </div>
          </div>

          <div class="col-12 col-md-7 col-lg-4">
            <label for="courseFilter" class="form-label fw-semibold small text-secondary">
              Capacitación
            </label>
            <select class="form-select" id="courseFilter">
              <option value="">Todas las capacitaciones</option>
            </select>
          </div>

          <div class="col-12 col-md-5 col-lg-3">
            <label for="statusFilter" class="form-label fw-semibold small text-secondary">
              Estado Curso
            </label>
            <select class="form-select" id="statusFilter">
              <option value="">Todos</option>
              <option value="VIGENTE">Vigentes</option>
              <option value="POR_VENCER">Por vencer (≤30d)</option>
              <option value="VENCIDO">Vencidos</option>
            </select>
          </div>
        </div>

        <hr class="my-3 text-muted opacity-25">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div class="d-flex flex-wrap gap-3 gap-md-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="switchLatestOnly" checked>
              <label class="form-check-label small fw-medium text-dark" for="switchLatestOnly">
                Ocultar cursos antiguos ya revalidados
              </label>
            </div>

            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="switchActiveWorkers">
              <label class="form-check-label small fw-medium text-dark" for="switchActiveWorkers">
                Solo personal vigente en Tareo (<code>VIGENCIA = SI</code>)
              </label>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm fw-semibold px-3" id="btnClear" type="button">
              <i class="bi bi-eraser-fill me-1"></i>Limpiar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Ficha Integral del Trabajador (Cruce con Tabla4 + Fotografía) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 d-none border-start border-primary border-4" id="workerCard">
      <div class="card-body p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="position-relative flex-shrink-0">
              <img id="workerPhoto" src="" alt="Foto trabajador"
                   class="rounded-3 border shadow-sm worker-avatar-lg d-none"
                   title="Clic para ampliar fotografía">
              <div id="workerPhotoFallback"
                   class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm"
                   style="width: 76px; height: 76px;">
                <i class="bi bi-person-vcard-fill fs-2"></i>
              </div>
            </div>

            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h2 class="h5 fw-bold mb-0 text-dark" id="workerName">TRABAJADOR</h2>
                <span class="badge bg-dark font-monospace" id="workerDni">DNI: -</span>
                <span id="workerVigenciaBadge"></span>
              </div>
              <div class="text-secondary small mt-1" id="workerJobInfo">
                Cargando ficha laboral...
              </div>
              <div class="d-flex flex-wrap gap-3 small text-muted mt-2" id="workerExtraInfo"></div>
            </div>
          </div>

          <div class="d-flex align-items-center gap-1 flex-wrap" id="workerBadges"></div>
        </div>
      </div>
    </div>

    <!-- Tabla de Resultados -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-uppercase small text-secondary">
            <tr>
              <th class="ps-3">DNI</th>
              <th>Apellidos y Nombres / Cargo</th>
              <th>Capacitación (Catálogo)</th>
              <th>Emisión</th>
              <th>Vencimiento</th>
              <th>Estado</th>
              <th class="text-center">Nota</th>
              <th class="text-end pe-3">Certificado</th>
            </tr>
          </thead>
          <tbody id="tableBody" class="small">
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Cargando registros...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 text-muted small flex-wrap gap-2">
      <span id="limitNote"></span>
      <span>Mostrando <strong id="statFiltered">0</strong> certificados</span>
    </div>

  </main>

  <!-- Modal Visor de PDF Integrado -->
  <div class="modal fade" id="pdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header bg-dark text-white py-2 px-3">
          <div class="text-truncate pe-2">
            <h6 class="modal-title mb-0 text-truncate" id="modalPdfTitle">Certificado PDF</h6>
            <small class="text-white-50" id="modalPdfSubtitle">-</small>
          </div>
          <div class="d-flex align-items-center gap-2 ms-auto">
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-light" id="btnPrevPdf" title="Anterior">
                <i class="bi bi-chevron-left"></i>
              </button>
              <button type="button" class="btn btn-outline-light" id="btnNextPdf" title="Siguiente">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
            <a href="#" id="modalDownloadBtn" download class="btn btn-primary btn-sm fw-semibold">
              <i class="bi bi-download me-1"></i>Descargar
            </a>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
        </div>
        <div class="modal-body p-0 bg-dark">
          <iframe id="pdfIframe" class="pdf-frame" src=""></iframe>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Visor de Fotografía -->
  <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
        <div class="modal-header bg-dark text-white py-2 px-3">
          <h6 class="modal-title mb-0 text-truncate small" id="photoModalTitle">Fotografía</h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-2 bg-light text-center">
          <img id="photoModalImg" src="" alt="Fotografía ampliada" class="img-fluid rounded-3 shadow-sm">
        </div>
        <div class="modal-footer py-2 px-3 bg-white justify-content-between">
          <small class="text-muted font-monospace" id="photoModalDni"></small>
          <a id="photoModalDownload" href="#" download class="btn btn-primary btn-sm">
            <i class="bi bi-download me-1"></i>Descargar
          </a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
  <script>
    const BASE_URL = "https://systemsft.com/CERTIFICADOS%20ISEM/";
    const PHOTO_BASE_URL = "FOTOGRAFIAS/";
    const PHOTO_EXTENSIONS = ["jpg", "jpeg", "png", "webp", "JPG", "JPEG", "PNG"];
    const MAX_ROWS_DEFAULT = 100;

    let rawRecords = [];
    let personalMap = {};
    let currentFiltered = [];
    let currentModalIndex = -1;
    const resolvedPhotoCache = {};

    const searchInput = document.getElementById("searchInput");
    const courseFilter = document.getElementById("courseFilter");
    const statusFilter = document.getElementById("statusFilter");
    const switchLatestOnly = document.getElementById("switchLatestOnly");
    const switchActiveWorkers = document.getElementById("switchActiveWorkers");
    const btnClear = document.getElementById("btnClear");
    const tableBody = document.getElementById("tableBody");
    const workerCard = document.getElementById("workerCard");

    function showModalSafe(modalId, fallbackUrl) {
      const el = document.getElementById(modalId);
      if (window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(el).show();
      } else if (fallbackUrl) {
        window.open(fallbackUrl, "_blank");
      }
    }

    function buildPdfUrl(dni, filename) {
      return `${BASE_URL}${encodeURIComponent(dni)}/${encodeURIComponent(filename)}`;
    }

    function escapeHtmlAttr(str) {
      return (str || "")
        .toString()
        .replace(/\u0026/g, "\u0026amp;")
        .replace(/\u0022/g, "\u0026quot;")
        .replace(/\u0027/g, "\u0026#39;")
        .replace(/\u003C/g, "\u0026lt;")
        .replace(/\u003E/g, "\u0026gt;");
    }

    function handlePhotoError(imgEl, dni) {
      const idx = Number(imgEl.dataset.extIdx || 0) + 1;
      if (idx < PHOTO_EXTENSIONS.length) {
        imgEl.dataset.extIdx = idx;
        imgEl.src = `${PHOTO_BASE_URL}${encodeURIComponent(dni)}.${PHOTO_EXTENSIONS[idx]}`;
      } else {
        resolvedPhotoCache[dni] = null;
        imgEl.classList.add("d-none");
        const fallback = imgEl.nextElementSibling;
        if (fallback) fallback.classList.remove("d-none");
      }
    }

    function handlePhotoLoad(imgEl, dni) {
      resolvedPhotoCache[dni] = imgEl.src;
      imgEl.classList.remove("d-none");
      const fallback = imgEl.nextElementSibling;
      if (fallback) fallback.classList.add("d-none");
    }

    function getInitialPhotoSrc(dni) {
      if (resolvedPhotoCache[dni]) return resolvedPhotoCache[dni];
      return `${PHOTO_BASE_URL}${encodeURIComponent(dni)}.${PHOTO_EXTENSIONS[0]}`;
    }

    function openPhotoModal(dni, nombre, imgSrc) {
      if (!imgSrc) return;
      document.getElementById("photoModalTitle").textContent = nombre;
      document.getElementById("photoModalDni").textContent = `DNI: ${dni}`;
      document.getElementById("photoModalImg").src = imgSrc;
      const dlBtn = document.getElementById("photoModalDownload");
      dlBtn.href = imgSrc;
      dlBtn.setAttribute("download", `${dni}_${nombre.replace(/\s+/g, "_")}`);
      showModalSafe("photoModal", imgSrc);
    }

    function normalizeText(str) {
      return (str || "")
        .toString()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase();
    }

    function getCourseFamily(courseName) {
      return normalizeText(courseName)
        .replace(/\s*-\s*revalidacion/g, "")
        .replace(/\s+revalidacion/g, "")
        .replace(/\s+/g, " ")
        .trim();
    }

    function parseFechaToTimestamp(fechaStr) {
      if (!fechaStr) return 0;
      const parts = fechaStr.split("-");
      if (parts.length === 3) {
        return new Date(Number(parts[2]), Number(parts[1]) - 1, Number(parts[0])).getTime();
      }
      if (/^\d{4}$/.test(fechaStr)) {
        return new Date(Number(fechaStr), 11, 31).getTime();
      }
      return 0;
    }

    function calcularEstado(vencIso) {
      if (!vencIso) {
        return { code: "SIN_FECHA", label: "Sin fecha", badgeClass: "bg-secondary-subtle text-secondary", dias: null };
      }
      const hoy = new Date();
      hoy.setHours(0, 0, 0, 0);

      const [y, m, d] = vencIso.split("-").map(Number);
      const vencDate = new Date(y, m - 1, d);
      const diffDias = Math.round((vencDate - hoy) / (1000 * 60 * 60 * 24));

      if (diffDias < 0) {
        return {
          code: "VENCIDO",
          label: `Vencido (${Math.abs(diffDias)}d)`,
          badgeClass: "bg-danger-subtle text-danger border border-danger-subtle",
          dias: diffDias
        };
      }
      if (diffDias <= 30) {
        return {
          code: "POR_VENCER",
          label: diffDias === 0 ? "Vence hoy" : `Vence en ${diffDias}d`,
          badgeClass: "bg-warning-subtle text-warning-emphasis border border-warning-subtle",
          dias: diffDias
        };
      }
      return {
        code: "VIGENTE",
        label: `Vigente (${diffDias}d)`,
        badgeClass: "bg-success-subtle text-success border border-success-subtle",
        dias: diffDias
      };
    }

    function deduplicateRecords(records) {
      const latestMap = new Map();
      records.forEach(r => {
        const key = `${r.d}|${r._family}`;
        const existing = latestMap.get(key);
        if (!existing || r._ts >= existing._ts) {
          latestMap.set(key, r);
        }
      });
      return Array.from(latestMap.values());
    }

    function getActiveBaseDataset() {
      let base = switchLatestOnly.checked ? deduplicateRecords(rawRecords) : rawRecords;
      if (switchActiveWorkers.checked) {
        base = base.filter(r => (personalMap[r.d]?.vigencia || "") === "SI");
      }
      return base;
    }

    function updateKPIs(baseDataset) {
      const vig = baseDataset.filter(r => r._estado.code === "VIGENTE").length;
      const porV = baseDataset.filter(r => r._estado.code === "POR_VENCER").length;
      const venc = baseDataset.filter(r => r._estado.code === "VENCIDO").length;
      const workers = new Set(baseDataset.map(r => r.d)).size;

      document.getElementById("statTotal").textContent = baseDataset.length.toLocaleString("es-PE");
      document.getElementById("statWorkers").textContent = `${workers.toLocaleString("es-PE")} trabajadores`;
      document.getElementById("statVigentes").textContent = vig.toLocaleString("es-PE");
      document.getElementById("statPorVencer").textContent = porV.toLocaleString("es-PE");
      document.getElementById("statVencidos").textContent = venc.toLocaleString("es-PE");
    }

    function filtrarPorTarjeta(estado) {
      statusFilter.value = estado;
      renderTable();
    }

    function populateFilters(records) {
      const courses = [...new Set(records.map(r => r.t))].sort((a, b) => a.localeCompare(b));
      courses.forEach(course => {
        const opt = document.createElement("option");
        opt.value = course;
        opt.textContent = course;
        courseFilter.appendChild(opt);
      });
    }

    function openPdfModal(index) {
      if (index < 0 || index >= currentFiltered.length) return;
      currentModalIndex = index;
      const r = currentFiltered[index];
      const url = buildPdfUrl(r.d, r.a);

      document.getElementById("modalPdfTitle").textContent = `${r.n} (${r.d})`;
      document.getElementById("modalPdfSubtitle").textContent = `${r.t} • Emitido: ${r.f} • Vence: ${r.v || "-"}`;
      document.getElementById("pdfIframe").src = url;
      document.getElementById("modalDownloadBtn").href = url;
      document.getElementById("modalDownloadBtn").setAttribute("download", r.a);

      document.getElementById("btnPrevPdf").disabled = index === 0;
      document.getElementById("btnNextPdf").disabled = index === currentFiltered.length - 1;
      showModalSafe("pdfModal", url);
    }

    document.getElementById("btnPrevPdf").addEventListener("click", () => openPdfModal(currentModalIndex - 1));
    document.getElementById("btnNextPdf").addEventListener("click", () => openPdfModal(currentModalIndex + 1));

    function renderTable() {
      const baseDataset = getActiveBaseDataset();
      updateKPIs(baseDataset);

      const rawQuery = searchInput.value.trim();
      const query = normalizeText(rawQuery);
      const selectedCourse = courseFilter.value;
      const selectedStatus = statusFilter.value;

      currentFiltered = baseDataset.filter(r => {
        if (selectedCourse && r.t !== selectedCourse) return false;
        if (selectedStatus && r._estado.code !== selectedStatus) return false;
        if (!query) return true;
        return r.d.includes(query) || normalizeText(r.n).includes(query);
      });

      document.getElementById("statFiltered").textContent = currentFiltered.length.toLocaleString("es-PE");

      const uniqueDnis = [...new Set(currentFiltered.map(r => r.d))];
      if (rawQuery.length >= 4 && uniqueDnis.length === 1 && currentFiltered.length > 0) {
        const dni = currentFiltered[0].d;
        const nombre = currentFiltered[0].n;
        const p = personalMap[dni] || {};

        workerCard.classList.remove("d-none");
        document.getElementById("workerName").textContent = nombre;
        document.getElementById("workerDni").textContent = `DNI: ${dni}`;

        const photoEl = document.getElementById("workerPhoto");
        const fallbackEl = document.getElementById("workerPhotoFallback");
        if (resolvedPhotoCache[dni] === null) {
          photoEl.classList.add("d-none");
          fallbackEl.classList.remove("d-none");
        } else {
          photoEl.dataset.extIdx = "0";
          photoEl.classList.add("d-none");
          fallbackEl.classList.remove("d-none");
          photoEl.onload = () => handlePhotoLoad(photoEl, dni);
          photoEl.onerror = () => handlePhotoError(photoEl, dni);
          photoEl.onclick = () => openPhotoModal(dni, nombre, photoEl.src);
          photoEl.src = getInitialPhotoSrc(dni);
        }

        const vigBadge = document.getElementById("workerVigenciaBadge");
        if (p.vigencia === "SI") {
          vigBadge.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle">VIGENTE EN OBRA</span>`;
        } else if (p.vigencia === "NO") {
          vigBadge.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border">CESADO / NO VIGENTE</span>`;
        } else {
          vigBadge.innerHTML = "";
        }

        document.getElementById("workerJobInfo").textContent = p.cargo
          ? `${p.cargo}${p.especialidad ? " • " + p.especialidad : ""}${p.linea ? " (" + p.linea + ")" : ""}`
          : "Sin datos adicionales en lista de Tareo";

        const extras = [];
        if (p.ot && p.ot !== "0") extras.push(`<span><i class="bi bi-briefcase-fill text-primary me-1"></i>OT: <strong>${p.ot}</strong></span>`);
        if (p.celular) extras.push(`<span><i class="bi bi-telephone-fill text-success me-1"></i>Cel: <strong>${p.celular}</strong></span>`);
        if (p.sangre) extras.push(`<span><i class="bi bi-droplet-fill text-danger me-1"></i>Grupo: <strong>${p.sangre}</strong></span>`);
        document.getElementById("workerExtraInfo").innerHTML = extras.join("");

        const vig = currentFiltered.filter(x => x._estado.code === "VIGENTE").length;
        const porV = currentFiltered.filter(x => x._estado.code === "POR_VENCER").length;
        const venc = currentFiltered.filter(x => x._estado.code === "VENCIDO").length;

        document.getElementById("workerBadges").innerHTML = `
          <span class="badge bg-success rounded-pill px-2 py-1">${vig} Vigente(s)</span>
          ${porV > 0 ? `<span class="badge bg-warning text-dark rounded-pill px-2 py-1">${porV} Por vencer</span>` : ""}
          ${venc > 0 ? `<span class="badge bg-danger rounded-pill px-2 py-1">${venc} Vencido(s)</span>` : ""}
        `;
      } else {
        workerCard.classList.add("d-none");
      }

      if (currentFiltered.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-3 d-block mb-2"></i>
              No se encontraron certificados con los criterios ingresados.
            </td>
          </tr>`;
        document.getElementById("limitNote").textContent = "";
        return;
      }

      const isSearching = query.length > 0 || selectedCourse !== "" || selectedStatus !== "";
      const visibleRows = isSearching ? currentFiltered : currentFiltered.slice(0, MAX_ROWS_DEFAULT);

      tableBody.innerHTML = visibleRows.map((r, idx) => {
        const url = buildPdfUrl(r.d, r.a);
        const est = r._estado;
        const p = personalMap[r.d] || {};
        const knownNoPhoto = resolvedPhotoCache[r.d] === null;
        const initialSrc = knownNoPhoto ? "" : getInitialPhotoSrc(r.d);
        const safeNameAttr = escapeHtmlAttr(r.n);

        return `
          <tr>
            <td class="ps-3">
              <span class="badge bg-light text-dark border font-monospace">${r.d}</span>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                ${knownNoPhoto ? "" : `
                  <img src="${initialSrc}" data-ext-idx="0" alt=""
                       data-dni="${r.d}" data-nombre="${safeNameAttr}"
                       class="rounded-circle border worker-avatar-sm flex-shrink-0 d-none"
                       onload="handlePhotoLoad(this, this.dataset.dni)"
                       onerror="handlePhotoError(this, this.dataset.dni)"
                       onclick="openPhotoModal(this.dataset.dni, this.dataset.nombre, this.src)"
                       title="Ver fotografía">
                `}
                <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width: 36px; height: 36px;">
                  <i class="bi bi-person-fill"></i>
                </div>
                <div>
                  <div class="fw-semibold text-dark">${r.n}</div>
                  <small class="text-muted">${p.cargo || ""}${p.ot && p.ot !== "0" ? " • OT " + p.ot : ""}</small>
                </div>
              </div>
            </td>
            <td>
              <div>${r.t}</div>
              <small class="text-muted">Duración: ${r.m} meses</small>
            </td>
            <td class="text-nowrap text-secondary">${r.f}</td>
            <td class="text-nowrap fw-medium text-dark">${r.v || "-"}</td>
            <td class="text-nowrap">
              <span class="badge ${est.badgeClass} rounded-pill px-2 py-1 fw-semibold">
                ${est.label}
              </span>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-dark border rounded-pill px-2 py-1 fw-bold">
                ${r.p}
              </span>
            </td>
            <td class="text-end pe-3 text-nowrap">
              <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-primary fw-medium" onclick="openPdfModal(${idx})" title="Vista rápida">
                  <i class="bi bi-eye-fill me-1"></i>Ver
                </button>
                <a class="btn btn-outline-secondary fw-medium" href="${url}" download="${r.a}" title="Descargar PDF">
                  <i class="bi bi-download"></i>
                </a>
              </div>
            </td>
          </tr>
        `;
      }).join("");

      if (!isSearching && currentFiltered.length > MAX_ROWS_DEFAULT) {
        document.getElementById("limitNote").textContent =
          `Mostrando los primeros ${MAX_ROWS_DEFAULT} de ${currentFiltered.length.toLocaleString("es-PE")} certificados. Usa los filtros para consultar toda la base.`;
      } else {
        document.getElementById("limitNote").textContent = "";
      }
    }

    async function init() {
      try {
        const res = await fetch(`datos_protegidos.php?v=${Date.now()}`, {
          credentials: "same-origin"
        });
        if (res.status === 403 || res.status === 401) {
          window.location.href = "login.php";
          return;
        }
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();

        personalMap = data.personal_map || {};
        rawRecords = (data.registros || []).map(r => ({
          ...r,
          _estado: calcularEstado(r.vi),
          _family: getCourseFamily(r.t),
          _ts: parseFechaToTimestamp(r.f)
        }));

        document.getElementById("badgeUpdate").innerHTML = `<i class="bi bi-clock-history"></i> Actualizado: ${data.actualizado || "OK"}`;

        populateFilters(rawRecords);
        renderTable();
      } catch (err) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="8" class="text-center py-4 text-danger">
              <i class="bi bi-exclamation-octagon-fill me-1"></i> Error: ${err.message}
            </td>
          </tr>`;
      }
    }

    searchInput.addEventListener("input", renderTable);
    courseFilter.addEventListener("change", renderTable);
    statusFilter.addEventListener("change", renderTable);
    switchLatestOnly.addEventListener("change", renderTable);
    switchActiveWorkers.addEventListener("change", renderTable);

    btnClear.addEventListener("click", () => {
      searchInput.value = "";
      courseFilter.value = "";
      statusFilter.value = "";
      switchLatestOnly.checked = true;
      switchActiveWorkers.checked = false;
      renderTable();
    });

    init();
  </script>
</body>
</html>