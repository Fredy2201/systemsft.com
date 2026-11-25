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
    .worker-row { cursor: pointer; transition: background-color 0.12s ease; }
    .pdf-frame { width: 100%; height: 75vh; border: none; background: #1e293b; }
    .worker-avatar-sm { width: 42px; height: 42px; object-fit: cover; cursor: pointer; }
    .worker-avatar-lg { width: 84px; height: 84px; object-fit: cover; cursor: pointer; }
    .missing-course-card { border-left: 3px solid #94a3b8; background-color: #f8fafc; transition: all 0.15s ease; }
    .missing-course-card:hover { background-color: #ffffff; border-left-color: #0d6efd; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
  </style>
</head>
<body>

  <!-- Navbar Superior -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php" onclick="volverAlListado(); return false;">
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

    <!-- =================================================================== -->
    <!-- VISTA 1: DIRECTORIO DE PERSONAL (BÚSQUEDA Y SELECCIÓN)              -->
    <!-- =================================================================== -->
    <div id="directoryView">

      <!-- Tarjetas de Estadísticas Globales -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-primary border-4 stat-card" onclick="filtrarPorTarjeta('')">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
              <div>
                <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem;">Personal Evaluado</div>
                <div class="fs-3 fw-bold text-dark mt-1" id="statWorkers">-</div>
                <small class="text-muted" id="statTotal">- certificados activos</small>
              </div>
              <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 p-md-3">
                <i class="bi bi-people-fill fs-4"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-success border-4 stat-card" onclick="filtrarPorTarjeta('ALL_VIGENTE')">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
              <div>
                <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem;">Cursos Vigentes</div>
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
          <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-warning border-4 stat-card" onclick="filtrarPorTarjeta('HAS_POR_VENCER')">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
              <div>
                <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem;">Por Vencer (≤30d)</div>
                <div class="fs-3 fw-bold text-warning-emphasis mt-1" id="statPorVencer">-</div>
                <small class="text-muted">Clic para ver afectados</small>
              </div>
              <div class="bg-warning bg-opacity-10 text-warning-emphasis rounded-3 p-2 p-md-3">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-danger border-4 stat-card" onclick="filtrarPorTarjeta('HAS_VENCIDO')">
            <div class="card-body d-flex align-items-center justify-content-between p-3">
              <div>
                <div class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem;">Cursos Vencidos</div>
                <div class="fs-3 fw-bold text-danger mt-1" id="statVencidos">-</div>
                <small class="text-muted">Clic para ver afectados</small>
              </div>
              <div class="bg-danger bg-opacity-10 text-danger rounded-3 p-2 p-md-3">
                <i class="bi bi-x-octagon-fill fs-4"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel de Búsqueda de Personal -->
      <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3 p-md-4">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-6">
              <label for="searchInput" class="form-label fw-semibold small text-secondary">
                Buscar Trabajador por DNI, Apellidos o Cargo
              </label>
              <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="Ej. 04207095, PAICO, SUPERVISOR..." autocomplete="off" />
              </div>
            </div>

            <div class="col-12 col-md-8 col-lg-4">
              <label for="workerStatusFilter" class="form-label fw-semibold small text-secondary">
                Filtrar Personal por Situación
              </label>
              <select class="form-select" id="workerStatusFilter">
                <option value="">Todos los trabajadores</option>
                <option value="ALL_VIGENTE">100% al día (Sin vencidos ni por vencer)</option>
                <option value="HAS_POR_VENCER">Con cursos por vencer (≤30d)</option>
                <option value="HAS_VENCIDO">Con cursos vencidos</option>
                <option value="HAS_MISSING">Con capacitaciones faltantes del catálogo</option>
              </select>
            </div>

            <div class="col-12 col-md-4 col-lg-2 d-grid">
              <button class="btn btn-outline-secondary fw-semibold" id="btnClear" type="button">
                <i class="bi bi-eraser-fill me-1"></i>Limpiar
              </button>
            </div>
          </div>

          <hr class="my-3 text-muted opacity-25">

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="switchActiveWorkers">
              <label class="form-check-label small fw-medium text-dark" for="switchActiveWorkers">
                Mostrar solo personal vigente en Tareo (<code>VIGENCIA = SI</code>)
              </label>
            </div>
            <small class="text-muted">
              <i class="bi bi-hand-index-thumb me-1"></i>Haz clic sobre un trabajador para ver sus capacitaciones y cursos faltantes
            </small>
          </div>
        </div>
      </div>

      <!-- Tabla de Trabajadores -->
      <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small text-secondary">
              <tr>
                <th class="ps-3">DNI</th>
                <th>Trabajador / Cargo</th>
                <th class="text-center">Vigencia Tareo</th>
                <th>Estado de Capacitaciones</th>
                <th class="text-center">Cobertura Catálogo</th>
                <th class="text-end pe-3">Acción</th>
              </tr>
            </thead>
            <tbody id="workersTableBody" class="small">
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                  <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                  Cargando directorio de personal...
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-3 text-muted small flex-wrap gap-2">
        <span id="catalogInfoNote"></span>
        <span>Mostrando <strong id="statFilteredWorkers">0</strong> trabajadores</span>
      </div>

    </div>

    <!-- =================================================================== -->
    <!-- VISTA 2: PERFIL INDIVIDUAL DEL TRABAJADOR (CURSOS Y FALTANTES)      -->
    <!-- =================================================================== -->
    <div id="workerDetailView" class="d-none">

      <!-- Barra Superior de Retorno -->
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <button type="button" class="btn btn-outline-dark btn-sm fw-semibold rounded-pill px-3" onclick="volverAlListado()">
          <i class="bi bi-arrow-left me-1"></i>Volver al listado de personal
        </button>

        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="switchDetailLatestOnly" checked>
          <label class="form-check-label small fw-medium text-dark" for="switchDetailLatestOnly">
            Ocultar cursos antiguos ya revalidados
          </label>
        </div>
      </div>

      <!-- Tarjeta Cabecera del Trabajador -->
      <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-primary border-4">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="position-relative flex-shrink-0">
                <img id="detailPhoto" src="" alt="Foto trabajador"
                     class="rounded-3 border shadow-sm worker-avatar-lg d-none"
                     title="Clic para ampliar fotografía">
                <div id="detailPhotoFallback"
                     class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm"
                     style="width: 84px; height: 84px;">
                  <i class="bi bi-person-vcard-fill fs-1"></i>
                </div>
              </div>

              <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                  <h2 class="h4 fw-bold mb-0 text-dark" id="detailName">TRABAJADOR</h2>
                  <span class="badge bg-dark font-monospace fs-6" id="detailDni">DNI: -</span>
                  <span id="detailVigenciaBadge"></span>
                </div>
                <div class="text-secondary mt-1 fw-medium" id="detailJobInfo">-</div>
                <div class="d-flex flex-wrap gap-3 small text-muted mt-2" id="detailExtraInfo"></div>
              </div>
            </div>

            <!-- Resumen numérico del trabajador -->
            <div class="d-flex gap-2 flex-wrap" id="detailSummaryPills"></div>
          </div>
        </div>
      </div>

      <!-- SECCIÓN A: CAPACITACIONES QUE SÍ TIENE -->
      <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-3 px-md-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-patch-check-fill text-success fs-5"></i>
            <h5 class="mb-0 fw-bold text-dark fs-6">Capacitaciones Registradas</h5>
          </div>
          <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3" id="detailCompletedCount">
            0 cursos
          </span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase small text-secondary">
              <tr>
                <th class="ps-3">Capacitación (Catálogo)</th>
                <th>Emisión</th>
                <th>Vencimiento</th>
                <th>Estado</th>
                <th class="text-center">Nota</th>
                <th class="text-end pe-3">Certificado</th>
              </tr>
            </thead>
            <tbody id="detailCoursesBody" class="small"></tbody>
          </table>
        </div>
      </div>

      <!-- SECCIÓN B: CAPACITACIONES QUE NO TIENE (BRECHA VS CATÁLOGO ISEM) -->
      <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-diamond-fill text-warning-emphasis fs-5"></i>
            <div>
              <h5 class="mb-0 fw-bold text-dark fs-6">Capacitaciones No Llevadas / Pendientes del Catálogo</h5>
              <small class="text-muted">Comparado contra el catálogo general de cursos ISEM registrados en el sistema</small>
            </div>
          </div>
          <span class="badge bg-secondary-subtle text-dark border rounded-pill px-3" id="detailMissingCount">
            0 faltantes
          </span>
        </div>
        <div class="card-body p-3 p-md-4" id="detailMissingContainer">
          <!-- Renderizado dinámico -->
        </div>
      </div>

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

    let rawRecords = [];
    let personalMap = {};
    let workersList = [];          // Lista agrupada por trabajador
    let catalogFamilies = new Map(); // familyKey -> { label, sampleDuration }
    let selectedWorkerDni = null;
    let detailVisibleRecords = [];
    let currentModalIndex = -1;
    const resolvedPhotoCache = {};

    const directoryView = document.getElementById("directoryView");
    const workerDetailView = document.getElementById("workerDetailView");
    const searchInput = document.getElementById("searchInput");
    const workerStatusFilter = document.getElementById("workerStatusFilter");
    const switchActiveWorkers = document.getElementById("switchActiveWorkers");
    const switchDetailLatestOnly = document.getElementById("switchDetailLatestOnly");
    const btnClear = document.getElementById("btnClear");
    const workersTableBody = document.getElementById("workersTableBody");

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

    function getCleanCatalogLabel(courseName) {
      return (courseName || "")
        .replace(/\s*-\s*REVALIDACI[ÓO]N/gi, "")
        .replace(/\s+REVALIDACI[ÓO]N/gi, "")
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

    // Construye el catálogo maestro de capacitaciones y agrupa los datos por trabajador
    function buildWorkersAndCatalog() {
      catalogFamilies.clear();
      rawRecords.forEach(r => {
        if (!catalogFamilies.has(r._family)) {
          catalogFamilies.set(r._family, {
            family: r._family,
            label: getCleanCatalogLabel(r.t),
            months: r.m
          });
        }
      });

      const latestAll = deduplicateRecords(rawRecords);
      const byDni = new Map();

      latestAll.forEach(r => {
        if (!byDni.has(r.d)) {
          byDni.set(r.d, {
            dni: r.d,
            nombre: r.n,
            latestCourses: [],
            allCourses: []
          });
        }
        byDni.get(r.d).latestCourses.push(r);
      });

      rawRecords.forEach(r => {
        if (byDni.has(r.d)) {
          byDni.get(r.d).allCourses.push(r);
        }
      });

      const totalCatalogCount = catalogFamilies.size;

      workersList = Array.from(byDni.values()).map(w => {
        const p = personalMap[w.dni] || {};
        const vig = w.latestCourses.filter(c => c._estado.code === "VIGENTE").length;
        const porV = w.latestCourses.filter(c => c._estado.code === "POR_VENCER").length;
        const venc = w.latestCourses.filter(c => c._estado.code === "VENCIDO").length;

        const completedFamilies = new Set(w.latestCourses.map(c => c._family));
        const missingCourses = [];
        catalogFamilies.forEach((catItem, famKey) => {
          if (!completedFamilies.has(famKey)) {
            missingCourses.push(catItem);
          }
        });
        missingCourses.sort((a, b) => a.label.localeCompare(b.label));

        return {
          ...w,
          cargo: p.cargo || "",
          especialidad: p.especialidad || "",
          linea: p.linea || "",
          ot: p.ot || "",
          celular: p.celular || "",
          sangre: p.sangre || "",
          vigencia: p.vigencia || "",
          vigentesCount: vig,
          porVencerCount: porV,
          vencidosCount: venc,
          completedFamiliesCount: completedFamilies.size,
          totalCatalogCount,
          missingCourses
        };
      }).sort((a, b) => a.nombre.localeCompare(b.nombre));

      document.getElementById("catalogInfoNote").textContent =
        `Catálogo ISEM detectado: ${totalCatalogCount} capacitaciones distintas.`;
    }

    function updateDirectoryKPIs(filteredWorkers) {
      let totalCerts = 0;
      let totalVig = 0;
      let totalPorV = 0;
      let totalVenc = 0;

      filteredWorkers.forEach(w => {
        totalCerts += w.latestCourses.length;
        totalVig += w.vigentesCount;
        totalPorV += w.porVencerCount;
        totalVenc += w.vencidosCount;
      });

      document.getElementById("statWorkers").textContent = filteredWorkers.length.toLocaleString("es-PE");
      document.getElementById("statTotal").textContent = `${totalCerts.toLocaleString("es-PE")} certificados activos`;
      document.getElementById("statVigentes").textContent = totalVig.toLocaleString("es-PE");
      document.getElementById("statPorVencer").textContent = totalPorV.toLocaleString("es-PE");
      document.getElementById("statVencidos").textContent = totalVenc.toLocaleString("es-PE");
    }

    function filtrarPorTarjeta(filtro) {
      workerStatusFilter.value = filtro;
      renderWorkersDirectory();
    }

    function renderWorkersDirectory() {
      const query = normalizeText(searchInput.value.trim());
      const statusMode = workerStatusFilter.value;
      const onlyActiveTareo = switchActiveWorkers.checked;

      const filtered = workersList.filter(w => {
        if (onlyActiveTareo && w.vigencia !== "SI") return false;
        if (statusMode === "ALL_VIGENTE" && (w.vencidosCount > 0 || w.porVencerCount > 0)) return false;
        if (statusMode === "HAS_POR_VENCER" && w.porVencerCount === 0) return false;
        if (statusMode === "HAS_VENCIDO" && w.vencidosCount === 0) return false;
        if (statusMode === "HAS_MISSING" && w.missingCourses.length === 0) return false;

        if (!query) return true;
        return w.dni.includes(query) ||
               normalizeText(w.nombre).includes(query) ||
               normalizeText(w.cargo).includes(query);
      });

      updateDirectoryKPIs(filtered);
      document.getElementById("statFilteredWorkers").textContent = filtered.length.toLocaleString("es-PE");

      if (filtered.length === 0) {
        workersTableBody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-person-x fs-3 d-block mb-2"></i>
              No se encontraron trabajadores con los filtros seleccionados.
            </td>
          </tr>`;
        return;
      }

      workersTableBody.innerHTML = filtered.map(w => {
        const knownNoPhoto = resolvedPhotoCache[w.dni] === null;
        const initialSrc = knownNoPhoto ? "" : getInitialPhotoSrc(w.dni);
        const safeNameAttr = escapeHtmlAttr(w.nombre);
        const pct = w.totalCatalogCount > 0
          ? Math.round((w.completedFamiliesCount / w.totalCatalogCount) * 100)
          : 0;

        let vigBadge = `<span class="badge bg-light text-muted border">Sin dato</span>`;
        if (w.vigencia === "SI") {
          vigBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle">VIGENTE</span>`;
        } else if (w.vigencia === "NO") {
          vigBadge = `<span class="badge bg-secondary-subtle text-secondary border">NO VIGENTE</span>`;
        }

        return `
          <tr class="worker-row" onclick="abrirPerfilTrabajador('${w.dni}')">
            <td class="ps-3">
              <span class="badge bg-light text-dark border font-monospace">${w.dni}</span>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                ${knownNoPhoto ? "" : `
                  <img src="${initialSrc}" data-ext-idx="0" alt=""
                       data-dni="${w.dni}" data-nombre="${safeNameAttr}"
                       class="rounded-circle border worker-avatar-sm flex-shrink-0 d-none"
                       onload="handlePhotoLoad(this, this.dataset.dni)"
                       onerror="handlePhotoError(this, this.dataset.dni)"
                       onclick="event.stopPropagation(); openPhotoModal(this.dataset.dni, this.dataset.nombre, this.src)"
                       title="Ampliar foto">
                `}
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width: 42px; height: 42px;">
                  <i class="bi bi-person-fill fs-5"></i>
                </div>
                <div>
                  <div class="fw-bold text-dark">${w.nombre}</div>
                  <small class="text-muted">${w.cargo || "Cargo no registrado en Tareo"}</small>
                </div>
              </div>
            </td>
            <td class="text-center text-nowrap">${vigBadge}</td>
            <td>
              <div class="d-flex flex-wrap gap-1">
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                  ${w.vigentesCount} Vigente(s)
                </span>
                ${w.porVencerCount > 0 ? `
                  <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill">
                    ${w.porVencerCount} Por vencer
                  </span>` : ""}
                ${w.vencidosCount > 0 ? `
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">
                    ${w.vencidosCount} Vencido(s)
                  </span>` : ""}
              </div>
            </td>
            <td class="text-center" style="min-width: 150px;">
              <div class="small fw-semibold text-dark mb-1">
                ${w.completedFamiliesCount} / ${w.totalCatalogCount} cursos
                <span class="text-muted fw-normal">(${w.missingCourses.length} faltantes)</span>
              </div>
              <div class="progress" style="height: 6px;">
                <div class="progress-bar ${pct === 100 ? 'bg-success' : 'bg-primary'}" style="width: ${pct}%"></div>
              </div>
            </td>
            <td class="text-end pe-3 text-nowrap">
              <button type="button" class="btn btn-primary btn-sm fw-semibold rounded-pill px-3">
                Ver Ficha <i class="bi bi-chevron-right ms-1"></i>
              </button>
            </td>
          </tr>
        `;
      }).join("");
    }

    function abrirPerfilTrabajador(dni) {
      const worker = workersList.find(w => w.dni === dni);
      if (!worker) return;
      selectedWorkerDni = dni;

      directoryView.classList.add("d-none");
      workerDetailView.classList.remove("d-none");
      window.scrollTo({ top: 0, behavior: "smooth" });

      // Cabecera del trabajador
      document.getElementById("detailName").textContent = worker.nombre;
      document.getElementById("detailDni").textContent = `DNI: ${worker.dni}`;

      const photoEl = document.getElementById("detailPhoto");
      const fallbackEl = document.getElementById("detailPhotoFallback");
      if (resolvedPhotoCache[dni] === null) {
        photoEl.classList.add("d-none");
        fallbackEl.classList.remove("d-none");
      } else {
        photoEl.dataset.extIdx = "0";
        photoEl.classList.add("d-none");
        fallbackEl.classList.remove("d-none");
        photoEl.onload = () => handlePhotoLoad(photoEl, dni);
        photoEl.onerror = () => handlePhotoError(photoEl, dni);
        photoEl.onclick = () => openPhotoModal(dni, worker.nombre, photoEl.src);
        photoEl.src = getInitialPhotoSrc(dni);
      }

      const vigBadge = document.getElementById("detailVigenciaBadge");
      if (worker.vigencia === "SI") {
        vigBadge.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle">VIGENTE EN OBRA</span>`;
      } else if (worker.vigencia === "NO") {
        vigBadge.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border">CESADO / NO VIGENTE</span>`;
      } else {
        vigBadge.innerHTML = "";
      }

      document.getElementById("detailJobInfo").textContent = worker.cargo
        ? `${worker.cargo}${worker.especialidad ? " • " + worker.especialidad : ""}${worker.linea ? " (" + worker.linea + ")" : ""}`
        : "Sin datos adicionales en lista de Tareo";

      const extras = [];
      if (worker.ot && worker.ot !== "0") extras.push(`<span><i class="bi bi-briefcase-fill text-primary me-1"></i>OT: <strong>${worker.ot}</strong></span>`);
      if (worker.celular) extras.push(`<span><i class="bi bi-telephone-fill text-success me-1"></i>Cel: <strong>${worker.celular}</strong></span>`);
      if (worker.sangre) extras.push(`<span><i class="bi bi-droplet-fill text-danger me-1"></i>Grupo: <strong>${worker.sangre}</strong></span>`);
      document.getElementById("detailExtraInfo").innerHTML = extras.join("");

      document.getElementById("detailSummaryPills").innerHTML = `
        <span class="badge bg-success rounded-pill px-3 py-2">${worker.vigentesCount} Vigente(s)</span>
        ${worker.porVencerCount > 0 ? `<span class="badge bg-warning text-dark rounded-pill px-3 py-2">${worker.porVencerCount} Por vencer</span>` : ""}
        ${worker.vencidosCount > 0 ? `<span class="badge bg-danger rounded-pill px-3 py-2">${worker.vencidosCount} Vencido(s)</span>` : ""}
        <span class="badge bg-secondary rounded-pill px-3 py-2">${worker.missingCourses.length} Faltante(s)</span>
      `;

      renderWorkerDetailCourses();
      renderWorkerMissingCourses(worker);
    }

    function renderWorkerDetailCourses() {
      const worker = workersList.find(w => w.dni === selectedWorkerDni);
      if (!worker) return;

      detailVisibleRecords = switchDetailLatestOnly.checked
        ? [...worker.latestCourses]
        : [...worker.allCourses];

      // Ordenar: primero vencidos, luego por vencer, luego vigentes
      const priority = { VENCIDO: 1, POR_VENCER: 2, VIGENTE: 3, SIN_FECHA: 4 };
      detailVisibleRecords.sort((a, b) => (priority[a._estado.code] || 9) - (priority[b._estado.code] || 9));

      document.getElementById("detailCompletedCount").textContent = `${detailVisibleRecords.length} curso(s)`;

      const tbody = document.getElementById("detailCoursesBody");
      if (detailVisibleRecords.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center py-4 text-muted">Este trabajador no tiene capacitaciones registradas.</td>
          </tr>`;
        return;
      }

      tbody.innerHTML = detailVisibleRecords.map((r, idx) => {
        const url = buildPdfUrl(r.d, r.a);
        const est = r._estado;
        return `
          <tr>
            <td class="ps-3">
              <div class="fw-semibold text-dark">${r.t}</div>
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
    }

    function renderWorkerMissingCourses(worker) {
      const container = document.getElementById("detailMissingContainer");
      const missing = worker.missingCourses;

      document.getElementById("detailMissingCount").textContent = `${missing.length} faltante(s)`;

      if (missing.length === 0) {
        container.innerHTML = `
          <div class="alert alert-success mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div><strong>¡Catálogo completo!</strong> Este trabajador cuenta con todas las capacitaciones registradas en el sistema.</div>
          </div>`;
        return;
      }

      container.innerHTML = `
        <div class="row g-2">
          ${missing.map(item => `
            <div class="col-12 col-md-6">
              <div class="p-3 rounded-3 border missing-course-card d-flex align-items-center justify-content-between gap-2 h-100">
                <div class="d-flex align-items-start gap-2">
                  <i class="bi bi-dash-circle text-secondary mt-1"></i>
                  <div>
                    <div class="fw-semibold text-dark small">${item.label}</div>
                    <small class="text-muted">Vigencia estándar: ${item.months} meses</small>
                  </div>
                </div>
                <span class="badge bg-light text-secondary border text-nowrap">No llevado</span>
              </div>
            </div>
          `).join("")}
        </div>
      `;
    }

    function volverAlListado() {
      selectedWorkerDni = null;
      workerDetailView.classList.add("d-none");
      directoryView.classList.remove("d-none");
    }

    function openPdfModal(index) {
      if (index < 0 || index >= detailVisibleRecords.length) return;
      currentModalIndex = index;
      const r = detailVisibleRecords[index];
      const url = buildPdfUrl(r.d, r.a);

      document.getElementById("modalPdfTitle").textContent = `${r.n} (${r.d})`;
      document.getElementById("modalPdfSubtitle").textContent = `${r.t} • Emitido: ${r.f} • Vence: ${r.v || "-"}`;
      document.getElementById("pdfIframe").src = url;
      document.getElementById("modalDownloadBtn").href = url;
      document.getElementById("modalDownloadBtn").setAttribute("download", r.a);

      document.getElementById("btnPrevPdf").disabled = index === 0;
      document.getElementById("btnNextPdf").disabled = index === detailVisibleRecords.length - 1;
      showModalSafe("pdfModal", url);
    }

    document.getElementById("btnPrevPdf").addEventListener("click", () => openPdfModal(currentModalIndex - 1));
    document.getElementById("btnNextPdf").addEventListener("click", () => openPdfModal(currentModalIndex + 1));

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

        buildWorkersAndCatalog();
        renderWorkersDirectory();
      } catch (err) {
        workersTableBody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center py-4 text-danger">
              <i class="bi bi-exclamation-octagon-fill me-1"></i> Error: ${err.message}
            </td>
          </tr>`;
      }
    }

    searchInput.addEventListener("input", renderWorkersDirectory);
    workerStatusFilter.addEventListener("change", renderWorkersDirectory);
    switchActiveWorkers.addEventListener("change", renderWorkersDirectory);
    switchDetailLatestOnly.addEventListener("change", renderWorkerDetailCourses);

    btnClear.addEventListener("click", () => {
      searchInput.value = "";
      workerStatusFilter.value = "";
      switchActiveWorkers.checked = false;
      renderWorkersDirectory();
    });

    init();
  </script>
</body>
</html>