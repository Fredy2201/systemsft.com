import os
import re
import unicodedata
import fitz  # PyMuPDF
from pyxlsb import open_workbook
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment

# 1. CONFIGURACIÓN DE RUTAS
RUTA_BASE = r"C:\Users\MYSSAP15-UC\Documents\AUXILIAR DE TAREO\CERTIFICADOS ISEM"
RUTA_TAREO_XLSB = r"C:\Users\MYSSAP15-UC\Documents\AUXILIAR DE TAREO\TAREO\2026\TAREO SEMANA 2 (version 1).xlsb"
HOJA_TAREO = "LISTA"
RUTA_CODIFICACION = os.path.join(RUTA_BASE, "CODIFICACION.xlsx")
HOJA_CATALOGO = "OFICIAL"
RUTA_REPORTE_EXCEL = os.path.join(RUTA_BASE, "REPORTE_CERTIFICADOS_ISEM_INTERNO.xlsx")

EQUIVALENCIAS = [
    ("AISLAMIENTO DE ENERGIA DE REVALIDACION AUTORIZADO", "Aislamiento de energía AUTORIZADO REVALIDACIÓN"),
    ("AISLAMIENTO DE ENERGIA REVALIDACION AUTORIZADO", "Aislamiento de energía AUTORIZADO REVALIDACIÓN"),
    ("AISLAMIENTO DE ENERGIA PERSONAL AUTORIZADO REVALIDACION", "Aislamiento de energía AUTORIZADO REVALIDACIÓN"),
    ("AISLAMIENTO DE ENERGIA PERSONAL AUTORIZADO", "Aislamiento de energía AUTORIZADO"),
    ("AISLAMIENTO DE ENERGIA PERSONAL COMPETENTE REVALIDACION", "Aislamiento de energía COMPETENTE REVALIDACIÓN"),
    ("AISLAMIENTO DE ENERGIA PERSONAL COMPETENTE", "Aislamiento de energía COMPETENTE"),
    ("ESPACIOS CONFINADOS PERSONAL AUTORIZADO REVALIDACION", "Seguridad en espacios confinados AUTORIZADO REVALIDACIÓN"),
    ("ESPACIOS CONFINADOS AUTORIZADO REVALIDACION", "Seguridad en espacios confinados AUTORIZADO REVALIDACIÓN"),
    ("ESPACIOS CONFINADOS PERSONAL AUTORIZADO", "Seguridad en espacios confinados AUTORIZADO"),
    ("ESPACIOS CONFINADOS AUTORIZADO", "Seguridad en espacios confinados AUTORIZADO"),
    ("ESPACIOS CONFINADOS COMPETENTE", "Seguridad en espacios confinados COMPETENTE"),
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
    ("EN CALIENTE PERSONAL AUTORIZADO REVALIDACION", "Seguridad en Trabajos en Caliente AUTORIZADO REVALIDACIÓN"),
    ("EN CALIENTE AUTORIZADO REVALIDACION", "Seguridad en Trabajos en Caliente AUTORIZADO REVALIDACIÓN"),
    ("EN CALIENTE PERSONAL AUTORIZADO", "Seguridad en Trabajos en Caliente AUTORIZADO"),
    ("EN CALIENTE AUTORIZADO", "Seguridad en Trabajos en Caliente AUTORIZADO"),
    ("EN CALIENTE PERSONAL COMPETENTE REVALIDACION", "Seguridad en Trabajos en Caliente COMPETENTE REVALIDACIÓN"),
    ("EN CALIENTE COMPETENTE REVALIDACION", "Seguridad en Trabajos en Caliente COMPETENTE REVALIDACIÓN"),
    ("EN CALIENTE PERSONAL COMPETENTE", "Seguridad en Trabajos en Caliente COMPETENTE"),
    ("EN CALIENTE COMPETENTE", "Seguridad en Trabajos en Caliente COMPETENTE"),
    ("IZAJE DE CARGA PERSONAL AUTORIZADO REVALIDACION", "Izaje de Cargas AUTORIZADO REVALIDACIÓN"),
    ("IZAJE DE CARGAS PERSONAL AUTORIZADO REVALIDACION", "Izaje de Cargas AUTORIZADO REVALIDACIÓN"),
    ("IZAJE DE CARGA AUTORIZADO REVALIDACION", "Izaje de Cargas AUTORIZADO REVALIDACIÓN"),
    ("IZAJE DE CARGA PERSONAL AUTORIZADO", "Izaje de Cargas AUTORIZADO"),
    ("IZAJE DE CARGAS PERSONAL AUTORIZADO", "Izaje de Cargas AUTORIZADO"),
    ("IZAJE DE CARGA AUTORIZADO", "Izaje de Cargas AUTORIZADO"),
    ("IZAJE DE CARGA PERSONAL COMPETENTE REVALIDACION", "Izaje de Cargas COMPETENTE REVALIDACIÓN"),
    ("IZAJE DE CARGAS PERSONAL COMPETENTE REVALIDACION", "Izaje de Cargas COMPETENTE REVALIDACIÓN"),
    ("IZAJE DE CARGA COMPETENTE REVALIDACION", "Izaje de Cargas COMPETENTE REVALIDACIÓN"),
    ("IZAJE DE CARGA PERSONAL COMPETENTE", "Izaje de Cargas COMPETENTE"),
    ("IZAJE DE CARGAS PERSONAL COMPETENTE", "Izaje de Cargas COMPETENTE"),
    ("IZAJE DE CARGA COMPETENTE", "Izaje de Cargas COMPETENTE"),
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

def cargar_nombres_tareo_xlsb(ruta_xlsb: str, nombre_hoja: str) -> dict[str, str]:
    """Lee DNI (Col B) y NOMBRE (Col C) desde la hoja LISTA del archivo binario .xlsb"""
    mapa_dni_nombre = {}
    if not os.path.exists(ruta_xlsb):
        print(f"⚠️ No se encontró el archivo de tareo: {ruta_xlsb}")
        return mapa_dni_nombre

    with open_workbook(ruta_xlsb) as wb:
        with wb.get_sheet(nombre_hoja) as sheet:
            for idx, row in enumerate(sheet.rows()):
                if idx == 0:
                    continue
                if len(row) > 2 and row[1].v is not None:
                    dni_raw = str(row[1].v).strip().split(".")[0]
                    dni_str = dni_raw.zfill(8)
                    nombre_str = str(row[2].v).strip() if row[2].v is not None else ""
                    if dni_str.isdigit() and nombre_str:
                        mapa_dni_nombre[dni_str] = nombre_str

    return mapa_dni_nombre

def cargar_tabla_codigos(ruta_excel: str, nombre_hoja: str):
    """Carga CODIGO (Col A) y CAPACITACIÓN (Col B) desde CODIFICACION.xlsx"""
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
                break

    if ws is None:
        raise ValueError(f"❌ No se encontró la hoja '{nombre_hoja}' en {ruta_excel}.")

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

def analizar_contenido_pdf(ruta_pdf: str, mapa_codigos: dict, catalogo_ordenado: list):
    """Extrae (codigo, capacitacion_oficial, fecha_dd_mm_aaaa, nota) desde el interior del PDF"""
    try:
        doc = fitz.open(ruta_pdf)
        texto = "\n".join(page.get_text("text") for page in doc)
        doc.close()
    except Exception as e:
        return ("ERROR", f"ERROR AL LEER PDF: {e}", "", "")

    texto_norm = normalizar(texto)

    # 1. Identificar código y capacitación oficial
    codigo_encontrado = None
    cap_oficial = None

    for cod, orig, norm in catalogo_ordenado:
        if norm in texto_norm:
            codigo_encontrado = cod
            cap_oficial = orig
            break

    if not codigo_encontrado:
        for var, oficial in EQUIVALENCIAS:
            if var in texto_norm:
                cap_oficial = oficial
                codigo_encontrado = mapa_codigos.get(oficial)
                break

    if not cap_oficial:
        m_literal = re.search(
            r"(?:curso|capacitaci[oó]n|taller|seminario|induccion|inducci[oó]n)\s*(?:de|:)?\s*[\"\“]?(.+?)[\"\”]?\s*(?:realizado|dictado|llevado|con una duraci[oó]n|el d[ií]a|del\s+\d)",
            texto,
            re.IGNORECASE | re.DOTALL
        )
        cap_literal = re.sub(r"\s+", " ", m_literal.group(1)).strip(" :-.\n\r") if m_literal else ""
        cap_oficial = cap_literal if cap_literal else "NO IDENTIFICADO (REVISAR PDF)"
        codigo_encontrado = "SIN_CODIGO"

    # 2. Extraer fecha exacta (DD-MM-AAAA)
    fecha_fmt = ""
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

    # 3. Extraer nota ("Calificación: 20.00")
    nota_val = ""
    m_nota = re.search(r"(?:Calificaci[oó]n|Nota)\s*:?\s*(\d{1,2}(?:[.,]\d{1,2})?)", texto, re.IGNORECASE)
    if m_nota:
        nota_str = m_nota.group(1).replace(",", ".")
        try:
            num = float(nota_str)
            nota_val = int(num) if num.is_integer() else num
        except ValueError:
            nota_val = nota_str

    return (codigo_encontrado or "SIN_CODIGO", cap_oficial, fecha_fmt, nota_val)

def generar_excel_desde_pdfs():
    if not os.path.exists(RUTA_CODIFICACION):
        print(f"❌ No se encontró el archivo de codificación: {RUTA_CODIFICACION}")
        return

    mapa_dni_nombre = cargar_nombres_tareo_xlsb(RUTA_TAREO_XLSB, HOJA_TAREO)
    print(f"👥 Se cargaron {len(mapa_dni_nombre)} trabajadores desde '{HOJA_TAREO}'.")

    mapa_codigos, catalogo_ordenado = cargar_tabla_codigos(RUTA_CODIFICACION, HOJA_CATALOGO)
    print(f"📘 Se cargaron {len(catalogo_ordenado)} códigos desde '{HOJA_CATALOGO}'.")

    registros = []
    total_pdfs = 0

    for carpeta in sorted(os.listdir(RUTA_BASE)):
        ruta_dni = os.path.join(RUTA_BASE, carpeta)
        if not os.path.isdir(ruta_dni):
            continue

        dni = carpeta.strip()
        nombre_trabajador = mapa_dni_nombre.get(dni, "NO ENCONTRADO EN TAREO")

        for archivo in sorted(os.listdir(ruta_dni)):
            if not archivo.lower().endswith(".pdf"):
                continue

            total_pdfs += 1
            ruta_pdf = os.path.join(ruta_dni, archivo)
            codigo, cap_oficial, fecha_pdf, nota_pdf = analizar_contenido_pdf(
                ruta_pdf, mapa_codigos, catalogo_ordenado
            )

            if not fecha_pdf:
                m_f = re.search(r"(\d{2}-\d{2}-202\d|202\d)", archivo)
                fecha_pdf = m_f.group(1) if m_f else ""

            registros.append({
                "dni": dni,
                "nombre": nombre_trabajador,
                "codigo": int(codigo) if str(codigo).isdigit() else codigo,
                "capacitacion": cap_oficial,
                "fecha": fecha_pdf,
                "nota": nota_pdf,
                "archivo": archivo
            })

            if total_pdfs % 50 == 0:
                print(f"⏳ Procesados {total_pdfs} PDFs...")

    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = "BASE_CERTIFICADOS_PDF"

    encabezados = ["DNI", "APELLIDOS Y NOMBRES", "CODIGO", "CAPACITACIÓN (CATÁLOGO)", "FECHA", "NOTA", "NOMBRE DEL ARCHIVO"]
    ws.append(encabezados)

    header_fill = PatternFill(start_color="1F4E78", end_color="1F4E78", fill_type="solid")
    header_font = Font(color="FFFFFF", bold=True)
    for col_idx in range(1, len(encabezados) + 1):
        celda = ws.cell(row=1, column=col_idx)
        celda.fill = header_fill
        celda.font = header_font
        celda.alignment = Alignment(horizontal="center", vertical="center")

    for reg in registros:
        ws.append([
            reg["dni"],
            reg["nombre"],
            reg["codigo"],
            reg["capacitacion"],
            reg["fecha"],
            reg["nota"],
            reg["archivo"]
        ])

    for row_idx in range(2, len(registros) + 2):
        ws.cell(row=row_idx, column=1).number_format = "@"
        ws.cell(row=row_idx, column=3).alignment = Alignment(horizontal="center")
        ws.cell(row=row_idx, column=5).alignment = Alignment(horizontal="center")
        ws.cell(row=row_idx, column=6).alignment = Alignment(horizontal="center")

    anchos = {"A": 14, "B": 40, "C": 10, "D": 55, "E": 15, "F": 10, "G": 45}
    for col_letra, ancho in anchos.items():
        ws.column_dimensions[col_letra].width = ancho

    wb.save(RUTA_REPORTE_EXCEL)
    print(f"\n✅ Se analizaron {total_pdfs} certificados PDF con cruce de nombres desde el Tareo.")
    print(f"📊 Excel generado en:\n   {RUTA_REPORTE_EXCEL}")

if __name__ == "__main__":
    generar_excel_desde_pdfs()