import os
import re
import unicodedata
import fitz  # PyMuPDF
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment

# 1. CONFIGURACIÓN DE RUTAS
RUTA_BASE = r"C:\Users\MYSSAP15-UC\Documents\AUXILIAR DE TAREO\CERTIFICADOS ISEM"
RUTA_CODIFICACION = os.path.join(RUTA_BASE, "CODIFICACION.xlsx")
HOJA_CATALOGO = "OFICIAL"
RUTA_LOG_RENOMBRADO = os.path.join(RUTA_BASE, "LOG_RENOMBRADO_CODIGOS.xlsx")

EQUIVALENCIAS = [
    # Variantes con "REVALIDACION" antes de AUTORIZADO / COMPETENTE
    ("AISLAMIENTO DE ENERGIA DE REVALIDACION AUTORIZADO", "Aislamiento de energía AUTORIZADO REVALIDACIÓN"),
    ("AISLAMIENTO DE ENERGIA REVALIDACION AUTORIZADO", "Aislamiento de energía AUTORIZADO REVALIDACIÓN"),
    ("AISLAMIENTO DE ENERGIA DE REVALIDACION COMPETENTE", "Aislamiento de energía COMPETENTE REVALIDACIÓN"),
    ("AISLAMIENTO DE ENERGIA REVALIDACION COMPETENTE", "Aislamiento de energía COMPETENTE REVALIDACIÓN"),
    ("ESPACIOS CONFINADOS DE REVALIDACION AUTORIZADO", "Seguridad en espacios confinados AUTORIZADO REVALIDACIÓN"),
    ("ESPACIOS CONFINADOS REVALIDACION AUTORIZADO", "Seguridad en espacios confinados AUTORIZADO REVALIDACIÓN"),
    ("EN ALTURA DE REVALIDACION AUTORIZADO", "Seguridad en trabajos en alturas AUTORIZADO REVALIDACIÓN"),
    ("EN ALTURAS DE REVALIDACION AUTORIZADO", "Seguridad en trabajos en alturas AUTORIZADO REVALIDACIÓN"),
    ("EN CALIENTE DE REVALIDACION AUTORIZADO", "Seguridad en Trabajos en Caliente AUTORIZADO REVALIDACIÓN"),
    ("EN CALIENTE REVALIDACION AUTORIZADO", "Seguridad en Trabajos en Caliente AUTORIZADO REVALIDACIÓN"),
    # Aislamiento de energía (cubre PERSONAL AUTORIZADO / COMPETENTE)
    ("AISLAMIENTO DE ENERGIA PERSONAL AUTORIZADO REVALIDACION", "Aislamiento de energía AUTORIZADO REVALIDACIÓN"),
    ("AISLAMIENTO DE ENERGIA PERSONAL AUTORIZADO", "Aislamiento de energía AUTORIZADO"),
    ("AISLAMIENTO DE ENERGIA PERSONAL COMPETENTE REVALIDACION", "Aislamiento de energía COMPETENTE REVALIDACIÓN"),
    ("AISLAMIENTO DE ENERGIA PERSONAL COMPETENTE", "Aislamiento de energía COMPETENTE"),
    # Espacios confinados
    ("ESPACIOS CONFINADOS PERSONAL AUTORIZADO REVALIDACION", "Seguridad en espacios confinados AUTORIZADO REVALIDACIÓN"),
    ("ESPACIOS CONFINADOS AUTORIZADO REVALIDACION", "Seguridad en espacios confinados AUTORIZADO REVALIDACIÓN"),
    ("ESPACIOS CONFINADOS PERSONAL AUTORIZADO", "Seguridad en espacios confinados AUTORIZADO"),
    ("ESPACIOS CONFINADOS AUTORIZADO", "Seguridad en espacios confinados AUTORIZADO"),
    ("ESPACIOS CONFINADOS PERSONAL COMPETENTE", "Seguridad en espacios confinados COMPETENTE"),
    ("ESPACIOS CONFINADOS COMPETENTE", "Seguridad en espacios confinados COMPETENTE"),
    # Trabajos en alturas (cubre singular/plural y PERSONAL)
    ("EN ALTURA PERSONAL AUTORIZADO REVALIDACION", "Seguridad en trabajos en alturas AUTORIZADO REVALIDACIÓN"),
    ("EN ALTURAS PERSONAL AUTORIZADO REVALIDACION", "Seguridad en trabajos en alturas AUTORIZADO REVALIDACIÓN"),
    ("EN ALTURA AUTORIZADO REVALIDACION", "Seguridad en trabajos en alturas AUTORIZADO REVALIDACIÓN"),
    ("EN ALTURAS AUTORIZADO REVALIDACION", "Seguridad en trabajos en alturas AUTORIZADO REVALIDACIÓN"),
    ("EN ALTURA PERSONAL AUTORIZADO", "Seguridad en trabajos en alturas AUTORIZADO"),
    ("EN ALTURAS PERSONAL AUTORIZADO", "Seguridad en trabajos en alturas AUTORIZADO"),
    ("EN ALTURA AUTORIZADO", "Seguridad en trabajos en alturas AUTORIZADO"),
    ("EN ALTURAS AUTORIZADO", "Seguridad en trabajos en alturas AUTORIZADO"),
    ("EN ALTURA PERSONAL COMPETENTE REVALIDACION", "Seguridad en trabajos en alturas COMPETENTE REVALIDACIÓN"),
    ("EN ALTURAS PERSONAL COMPETENTE REVALIDACION", "Seguridad en trabajos en alturas COMPETENTE REVALIDACIÓN"),
    ("EN ALTURA COMPETENTE REVALIDACION", "Seguridad en trabajos en alturas COMPETENTE REVALIDACIÓN"),
    ("EN ALTURAS COMPETENTE REVALIDACION", "Seguridad en trabajos en alturas COMPETENTE REVALIDACIÓN"),
    ("EN ALTURA PERSONAL COMPETENTE", "Seguridad en trabajos en alturas COMPETENTE"),
    ("EN ALTURAS PERSONAL COMPETENTE", "Seguridad en trabajos en alturas COMPETENTE"),
    ("EN ALTURA COMPETENTE", "Seguridad en trabajos en alturas COMPETENTE"),
    ("EN ALTURAS COMPETENTE", "Seguridad en trabajos en alturas COMPETENTE"),
    # Trabajos en caliente
    ("EN CALIENTE PERSONAL AUTORIZADO REVALIDACION", "Seguridad en Trabajos en Caliente AUTORIZADO REVALIDACIÓN"),
    ("EN CALIENTE AUTORIZADO REVALIDACION", "Seguridad en Trabajos en Caliente AUTORIZADO REVALIDACIÓN"),
    ("EN CALIENTE PERSONAL AUTORIZADO", "Seguridad en Trabajos en Caliente AUTORIZADO"),
    ("EN CALIENTE AUTORIZADO", "Seguridad en Trabajos en Caliente AUTORIZADO"),
    ("EN CALIENTE PERSONAL COMPETENTE REVALIDACION", "Seguridad en Trabajos en Caliente COMPETENTE REVALIDACIÓN"),
    ("EN CALIENTE COMPETENTE REVALIDACION", "Seguridad en Trabajos en Caliente COMPETENTE REVALIDACIÓN"),
    ("EN CALIENTE PERSONAL COMPETENTE", "Seguridad en Trabajos en Caliente COMPETENTE"),
    ("EN CALIENTE COMPETENTE", "Seguridad en Trabajos en Caliente COMPETENTE"),
    # Izaje de cargas
    ("IZAJE DE CARGAS PERSONAL AUTORIZADO REVALIDACION", "Izaje de Cargas AUTORIZADO REVALIDACIÓN"),
    ("IZAJE DE CARGAS PERSONAL AUTORIZADO", "Izaje de Cargas AUTORIZADO"),
    ("IZAJE DE CARGAS PERSONAL COMPETENTE REVALIDACION", "Izaje de Cargas COMPETENTE REVALIDACIÓN"),
    ("IZAJE DE CARGAS PERSONAL COMPETENTE", "Izaje de Cargas COMPETENTE"),
    # Inducción General e Inducción Parada de Horno
    ("INDUCCION GENERAL DE SEGURIDAD Y SALUD EN EL TRABAJO REVALIDACION", "Inducción General de seguridad y salud en el Trabajo - REVALIDACIÓN"),
    ("INDUCCION GENERAL", "Inducción General de seguridad y salud en el Trabajo"),
    ("INDUCCION SEGURIDAD", "Inducción General de seguridad y salud en el Trabajo"),
    ("INDUCCION PARADA DE HORNO", "Inducción por parada de Horno"),
    ("PARADA DE HORNO", "Inducción por parada de Horno"),
]

MESES = {
    "enero": "01", "febrero": "02", "marzo": "03", "abril": "04",
    "mayo": "05", "junio": "06", "julio": "07", "agosto": "08",
    "septiembre": "09", "setiembre": "09", "octubre": "10", "noviembre": "11", "diciembre": "12"
}

def normalizar(texto: str) -> str:
    texto = unicodedata.normalize("NFD", str(texto))
    texto = "".join(c for c in texto if unicodedata.category(c) != "Mn")
    texto = re.sub(r"[^a-zA-Z0-9\s]", " ", texto)
    return re.sub(r"\s+", " ", texto).strip().upper()

def cargar_tabla_codigos(ruta_excel: str, nombre_hoja: str):
    """Busca la hoja OFICIAL (o la que contenga CODIGO en A1) y carga el catálogo"""
    wb = openpyxl.load_workbook(ruta_excel, data_only=True)
    ws = None

    for hoja in wb.sheetnames:
        if hoja.strip().upper() == nombre_hoja.strip().upper():
            ws = wb[hoja]
            break

    if ws is None:
        for hoja in wb.sheetnames:
            val_a1 = str(wb[hoja]["A1"].value or "").strip().upper()
            if val_a1 == "CODIGO":
                ws = wb[hoja]
                print(f"ℹ️ Usando la hoja '{hoja}' donde se encontró la tabla de códigos.")
                break

    if ws is None:
        raise ValueError(f"❌ No se encontró la hoja '{nombre_hoja}'. Hojas disponibles en el archivo: {wb.sheetnames}")

    mapa_oficial_a_codigo = {}
    catalogo_ordenado = []

    for fila in ws.iter_rows(min_row=2, values_only=True):
        codigo = fila[0]
        nombre_cap = fila[1]
        if codigo is not None and nombre_cap:
            cod_str = str(codigo).strip().split(".")[0]
            nombre_str = str(nombre_cap).strip()
            norm_str = normalizar(nombre_str)

            mapa_oficial_a_codigo[nombre_str] = cod_str
            catalogo_ordenado.append((cod_str, nombre_str, norm_str))

    catalogo_ordenado.sort(key=lambda x: len(x[2]), reverse=True)
    return mapa_oficial_a_codigo, catalogo_ordenado

def extraer_datos_pdf(ruta_pdf: str, mapa_codigos: dict, catalogo_ordenado: list):
    try:
        doc = fitz.open(ruta_pdf)
        texto = "\n".join(page.get_text("text") for page in doc)
        doc.close()
    except Exception:
        return None, None, None

    texto_norm = normalizar(texto)

    # 1. Buscar coincidencia directa con el catálogo de CODIFICACION.xlsx
    codigo_encontrado = None
    cap_oficial = None

    for cod, orig, norm in catalogo_ordenado:
        if norm in texto_norm:
            codigo_encontrado = cod
            cap_oficial = orig
            break

    # 2. Si no está idéntico, buscar en equivalencias del portal
    if not codigo_encontrado:
        for variante, oficial in EQUIVALENCIAS:
            if variante in texto_norm:
                cap_oficial = oficial
                codigo_encontrado = mapa_codigos.get(oficial)
                break

    # 3. Extraer fecha exacta (DD-MM-AAAA) desde el interior del PDF
    fecha_fmt = None
    m_fecha_texto = re.search(
        r"(\d{1,2})\s+de\s+([a-zA-ZáéíóúÁÉÍÓÚ]+)\s+(?:de|del)\s+(202\d)",
        texto,
        re.IGNORECASE
    )
    m_fecha_num = re.search(r"(\d{1,2})[/-](\d{1,2})[/-](202\d)", texto)

    if m_fecha_texto:
        dia = m_fecha_texto.group(1).zfill(2)
        mes_raw = normalizar(m_fecha_texto.group(2)).lower()
        mes_num = MESES.get(mes_raw, "00")
        anio = m_fecha_texto.group(3)
        fecha_fmt = f"{dia}-{mes_num}-{anio}"
    elif m_fecha_num:
        dia = m_fecha_num.group(1).zfill(2)
        mes_num = m_fecha_num.group(2).zfill(2)
        anio = m_fecha_num.group(3)
        fecha_fmt = f"{dia}-{mes_num}-{anio}"

    return codigo_encontrado, cap_oficial, fecha_fmt

def renombrar_pdfs_masivo():
    if not os.path.exists(RUTA_CODIFICACION):
        print(f"❌ No se encontró el archivo: {RUTA_CODIFICACION}")
        return

    mapa_codigos, catalogo_ordenado = cargar_tabla_codigos(RUTA_CODIFICACION, HOJA_CATALOGO)
    print(f"📘 Se cargaron {len(catalogo_ordenado)} códigos desde '{HOJA_CATALOGO}'.")

    registros_log = []
    renombrados = 0
    sin_identificar = 0

    for carpeta in sorted(os.listdir(RUTA_BASE)):
        ruta_dni = os.path.join(RUTA_BASE, carpeta)
        if not os.path.isdir(ruta_dni):
            continue

        dni = carpeta.strip()

        for archivo in sorted(os.listdir(ruta_dni)):
            if not archivo.lower().endswith(".pdf"):
                continue

            ruta_actual = os.path.join(ruta_dni, archivo)
            codigo, cap_oficial, fecha_pdf = extraer_datos_pdf(ruta_actual, mapa_codigos, catalogo_ordenado)

            # Respaldo de fecha desde el nombre actual si el PDF no tuviera texto seleccionable
            if not fecha_pdf:
                m_f = re.search(r"(\d{2}-\d{2}-202\d|202\d)", archivo)
                fecha_pdf = m_f.group(1) if m_f else "SIN_FECHA"

            if not codigo:
                print(f"⚠️ Sin código identificado: {dni}\\{archivo}")
                sin_identificar += 1
                registros_log.append([dni, "NO IDENTIFICADO", "-", fecha_pdf, archivo, archivo, "PENDIENTE REVISIÓN"])
                continue

            # FORMATO: "DNI - CODIGO - FECHA.pdf"
            nuevo_nombre = f"{dni} - {codigo} - {fecha_pdf}.pdf"
            ruta_nueva = os.path.join(ruta_dni, nuevo_nombre)

            if ruta_actual == ruta_nueva:
                registros_log.append([dni, codigo, cap_oficial, fecha_pdf, archivo, nuevo_nombre, "YA TENÍA EL NOMBRE"])
                continue

            # Evitar sobrescritura si hubiera dos certificados del mismo código y misma fecha
            if os.path.exists(ruta_nueva):
                contador = 2
                while os.path.exists(os.path.join(ruta_dni, f"{dni} - {codigo} - {fecha_pdf} ({contador}).pdf")):
                    contador += 1
                nuevo_nombre = f"{dni} - {codigo} - {fecha_pdf} ({contador}).pdf"
                ruta_nueva = os.path.join(ruta_dni, nuevo_nombre)

            os.rename(ruta_actual, ruta_nueva)
            renombrados += 1
            print(f"✅ {archivo}  -->  {nuevo_nombre}")
            registros_log.append([dni, codigo, cap_oficial, fecha_pdf, archivo, nuevo_nombre, "RENOMBRADO"])

    # Guardar reporte de auditoría del renombrado
    wb_out = openpyxl.Workbook()
    ws_out = wb_out.active
    ws_out.title = "RENOMBRADOS"
    headers = ["DNI", "CODIGO", "CAPACITACIÓN", "FECHA", "NOMBRE ANTERIOR", "NOMBRE NUEVO", "ESTADO"]
    ws_out.append(headers)

    fill = PatternFill(start_color="1F4E78", end_color="1F4E78", fill_type="solid")
    font = Font(color="FFFFFF", bold=True)
    for c in range(1, len(headers) + 1):
        cell = ws_out.cell(row=1, column=c)
        cell.fill = fill
        cell.font = font
        cell.alignment = Alignment(horizontal="center")

    for row in registros_log:
        ws_out.append(row)

    for r in range(2, len(registros_log) + 2):
        ws_out.cell(row=r, column=1).number_format = "@"

    for col_letra, ancho in {"A": 13, "B": 10, "C": 50, "D": 14, "E": 55, "F": 35, "G": 20}.items():
        ws_out.column_dimensions[col_letra].width = ancho

    wb_out.save(RUTA_LOG_RENOMBRADO)
    print(f"\n🏁 Proceso terminado: {renombrados} PDFs renombrados | {sin_identificar} sin identificar.")
    print(f"📊 Log guardado en: {RUTA_LOG_RENOMBRADO}")

if __name__ == "__main__":
    renombrar_pdfs_masivo()