import os
import re
import time
import unicodedata
from datetime import datetime
import fitz  # PyMuPDF
from pyxlsb import open_workbook
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment
from playwright.sync_api import sync_playwright

# 1. CONFIGURACIÓN DE RUTAS Y PARÁMETROS
RUTA_EXCEL = r"C:\Users\MYSSAP15-UC\Documents\AUXILIAR DE TAREO\TAREO\2026\TAREO SEMANA 2 (version 1).xlsb"
HOJA_EXCEL = "LISTA"
RUTA_BASE = r"C:\Users\MYSSAP15-UC\Documents\AUXILIAR DE TAREO\CERTIFICADOS ISEM"
RUTA_CODIFICACION = os.path.join(RUTA_BASE, "CODIFICACION.xlsx")
HOJA_CATALOGO = "OFICIAL"
RUTA_REPORTE_EXCEL = os.path.join(RUTA_BASE, "REPORTE_CERTIFICADOS_ISEM.xlsx")

ESPERA_PAGINA = 5  # Segundos de espera para que cargue cada página
FECHA_MINIMA = datetime(2024, 1, 1)  # Ignorar cursos anteriores al 01/01/2024

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
    # Aislamiento de energía
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
    # Trabajos en alturas
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
    # Izaje de cargas (singular y plural)
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
    # Inducciones y otros
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

def leer_dnis_xlsb(ruta_excel: str, nombre_hoja: str) -> list[str]:
    dnis = []
    with open_workbook(ruta_excel) as wb:
        with wb.get_sheet(nombre_hoja) as sheet:
            for idx, row in enumerate(sheet.rows()):
                if idx == 0:
                    continue
                if len(row) > 1 and row[1].v is not None:
                    valor_raw = str(row[1].v).strip().split(".")[0]
                    dni_str = valor_raw.zfill(8)
                    if dni_str.isdigit():
                        dnis.append(dni_str)
    return dnis

def analizar_pdf_en_memoria(pdf_bytes: bytes, mapa_codigos: dict, catalogo_ordenado: list):
    """Extrae código, capacitación oficial, fecha exacta y nota desde los bytes del PDF"""
    try:
        doc = fitz.open(stream=pdf_bytes, filetype="pdf")
        texto = "\n".join(page.get_text("text") for page in doc)
        doc.close()
    except Exception:
        return None, None, None, None

    texto_norm = normalizar(texto)

    # 1. Buscar coincidencia directa en la tabla OFICIAL
    codigo_encontrado = None
    cap_oficial = None

    for cod, orig, norm in catalogo_ordenado:
        if norm in texto_norm:
            codigo_encontrado = cod
            cap_oficial = orig
            break

    # 2. Respaldo con equivalencias
    if not codigo_encontrado:
        for variante, oficial in EQUIVALENCIAS:
            if variante in texto_norm:
                cap_oficial = oficial
                codigo_encontrado = mapa_codigos.get(oficial)
                break

    # 3. Extraer fecha exacta (DD-MM-AAAA) del contenido interno del PDF
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

    # 4. Extraer calificación ("Calificación: 20.00" o "Nota: 18")
    nota_val = None
    m_nota = re.search(r"(?:Calificaci[oó]n|Nota)\s*:?\s*(\d{1,2}(?:[.,]\d{1,2})?)", texto, re.IGNORECASE)
    if m_nota:
        nota_str = m_nota.group(1).replace(",", ".")
        try:
            nota_val = float(nota_str)
            if nota_val.is_integer():
                nota_val = int(nota_val)
        except ValueError:
            nota_val = nota_str

    return codigo_encontrado, cap_oficial, fecha_fmt, nota_val

def guardar_reporte_excel(registros: list[dict], ruta_salida: str):
    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = "CERTIFICADOS_ISEM"

    encabezados = ["DNI", "CODIGO", "CAPACITACIÓN", "FECHA", "NOTA", "NOMBRE DEL ARCHIVO"]
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
            reg["codigo"],
            reg["capacitacion"],
            reg["fecha"],
            reg["nota"],
            reg["archivo"]
        ])

    for row_idx in range(2, len(registros) + 2):
        ws.cell(row=row_idx, column=1).number_format = "@"
        ws.cell(row=row_idx, column=5).alignment = Alignment(horizontal="center")

    anchos = {"A": 14, "B": 10, "C": 55, "D": 15, "E": 10, "F": 45}
    for col_letra, ancho in anchos.items():
        ws.column_dimensions[col_letra].width = ancho

    wb.save(ruta_salida)
    print(f"\n📊 Base Excel generada correctamente en:\n   {ruta_salida}")

def ejecutar_descarga_masiva():
    mapa_codigos, catalogo_ordenado = cargar_tabla_codigos(RUTA_CODIFICACION, HOJA_CATALOGO)
    print(f"📘 Catálogo cargado con {len(catalogo_ordenado)} códigos desde '{HOJA_CATALOGO}'.")

    dnis = leer_dnis_xlsb(RUTA_EXCEL, HOJA_EXCEL)
    print(f"📋 Se cargaron {len(dnis)} DNIs desde el archivo binario (hoja '{HOJA_EXCEL}').")

    registros_excel = []

    with sync_playwright() as p:
        browser = p.chromium.connect_over_cdp("http://localhost:9222")
        context = browser.contexts[0]
        page = next((pg for pg in context.pages if "isem.org.pe" in pg.url), None)

        if not page:
            print("❌ No se encontró una pestaña abierta con isem.org.pe")
            return

        for idx, dni in enumerate(dnis, start=1):
            print(f"\n[{idx}/{len(dnis)}] 🔎 Buscando DNI: {dni}...")

            # 1. Ir a Personal -> Todo el Personal
            enlace_todo = page.locator("a:has-text('Todo el Personal')")
            if not enlace_todo.is_visible():
                page.locator("a:has-text('Personal')").first.click()
                time.sleep(1)
            enlace_todo.click()
            time.sleep(ESPERA_PAGINA)

            # 2. Escribir DNI y buscar
            input_busqueda = page.locator("input[placeholder*='DNI'], input[placeholder*='Nombres']").first
            input_busqueda.fill(dni)
            input_busqueda.press("Enter")
            time.sleep(ESPERA_PAGINA)

            # 3. Entrar a Ver Cursos
            btn_ver_cursos = page.locator("a:has-text('Ver Cursos'), button:has-text('Ver Cursos')").first
            if btn_ver_cursos.count() == 0 or not btn_ver_cursos.is_visible():
                print(f"⚠️ No se encontró al trabajador con DNI {dni} en el portal.")
                continue

            btn_ver_cursos.click()
            time.sleep(ESPERA_PAGINA)

            # 4. Crear carpeta con el DNI si no existe
            carpeta_dni = os.path.join(RUTA_BASE, dni)
            os.makedirs(carpeta_dni, exist_ok=True)

            # 5. Analizar filas de cursos
            filas = page.locator("table tbody tr")
            total_filas = filas.count()
            descargados = 0

            for i in range(total_filas):
                fila = filas.nth(i)
                if "Certificado VIGENTE" not in fila.inner_text():
                    continue

                texto_curso = fila.locator("td").nth(0).inner_text()
                anio_curso = fila.locator("td").nth(1).inner_text().strip()

                if anio_curso.isdigit() and int(anio_curso) < 2024:
                    print(f"🚫 Ignorado (año {anio_curso} < 2024)")
                    continue

                enlace = fila.locator("a[href*='/imprimir/']").first
                url_pdf = enlace.get_attribute("href")
                if not url_pdf:
                    continue
                if not url_pdf.startswith("http"):
                    url_pdf = f"https://isem.org.pe{url_pdf}"

                # --- PASO A: DESCARGAR PDF EN MEMORIA ---
                resp = context.request.get(url_pdf)
                if not resp.ok:
                    print(f"⚠️ Error al descargar PDF desde {url_pdf}")
                    continue
                pdf_bytes = resp.body()

                # --- PASO B: LEER EL INTERIOR DEL PDF (CÓDIGO, FECHA Y NOTA) ---
                codigo_cap, nombre_cap, fecha_pdf, nota_pdf = analizar_pdf_en_memoria(
                    pdf_bytes, mapa_codigos, catalogo_ordenado
                )

                # Respaldo de fecha desde la fila web si el PDF fuese imagen escaneada
                if not fecha_pdf:
                    m_ft = re.search(r"(\d{1,2})\s+de\s+([a-zA-ZáéíóúÁÉÍÓÚ]+)", texto_curso, re.IGNORECASE)
                    if m_ft:
                        dia = m_ft.group(1).zfill(2)
                        mes_num = MESES.get(normalizar(m_ft.group(2)).lower(), "01")
                        fecha_pdf = f"{dia}-{mes_num}-{anio_curso}"
                    else:
                        fecha_pdf = anio_curso

                # Validar fecha mínima exacta (>= 01/01/2024)
                try:
                    if len(fecha_pdf) == 10:
                        fecha_obj = datetime.strptime(fecha_pdf, "%d-%m-%Y")
                        if fecha_obj < FECHA_MINIMA:
                            print(f"🚫 Ignorado (anterior a 2024): {fecha_pdf}")
                            continue
                except ValueError:
                    pass

                # Definir nombre final: "DNI - CODIGO - FECHA.pdf"
                if codigo_cap:
                    nombre_archivo = f"{dni} - {codigo_cap} - {fecha_pdf}.pdf"
                else:
                    nombre_cap = "SIN_CODIGO"
                    codigo_cap = "SIN_CODIGO"
                    nombre_archivo = f"{dni} - SIN_CODIGO - {fecha_pdf}.pdf"

                ruta_destino = os.path.join(carpeta_dni, nombre_archivo)

                # --- PASO C: GUARDAR SOLO SI NO EXISTE EN LA CARPETA ---
                if os.path.exists(ruta_destino):
                    print(f"⏭️ Ya existe, saltando: {nombre_archivo}")
                else:
                    with open(ruta_destino, "wb") as f:
                        f.write(pdf_bytes)
                    print(f"✅ Descargado y nombrado desde PDF: {nombre_archivo} (Nota: {nota_pdf})")
                    descargados += 1

                registros_excel.append({
                    "dni": dni,
                    "codigo": codigo_cap,
                    "capacitacion": nombre_cap,
                    "fecha": fecha_pdf,
                    "nota": nota_pdf if nota_pdf is not None else "",
                    "archivo": nombre_archivo
                })

            print(f"📁 Resumen DNI {dni}: {descargados} certificados nuevos guardados.")

        if registros_excel:
            guardar_reporte_excel(registros_excel, RUTA_REPORTE_EXCEL)
        else:
            print("\n⚠️ No se encontraron certificados válidos para exportar a Excel.")

        print("\n🏁 Proceso masivo finalizado.")

if __name__ == "__main__":
    ejecutar_descarga_masiva()