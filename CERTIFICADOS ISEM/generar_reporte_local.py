import os
import openpyxl
from openpyxl.styles import Font, PatternFill, Alignment

# CONFIGURACIÓN DE RUTAS
RUTA_BASE = r"C:\Users\MYSSAP15-UC\Documents\AUXILIAR DE TAREO\CERTIFICADOS ISEM"
RUTA_REPORTE_EXCEL = os.path.join(RUTA_BASE, "REPORTE_CERTIFICADOS_ISEM.xlsx")

def generar_reporte_desde_carpetas():
    registros = []

    if not os.path.exists(RUTA_BASE):
        print(f"❌ No existe la ruta: {RUTA_BASE}")
        return

    # Recorrer cada subcarpeta de DNI en orden
    for nombre_carpeta in sorted(os.listdir(RUTA_BASE)):
        ruta_dni = os.path.join(RUTA_BASE, nombre_carpeta)
        
        if not os.path.isdir(ruta_dni):
            continue

        for archivo in sorted(os.listdir(ruta_dni)):
            if not archivo.lower().endswith(".pdf"):
                continue

            # Quitar extensión .pdf y dividir por " - "
            nombre_sin_ext = archivo[:-4]
            partes = nombre_sin_ext.split(" - ")

            if len(partes) >= 3:
                dni = partes[0].strip()
                fecha = partes[-1].strip()
                # Une los fragmentos intermedios por si el curso contiene un guion (ej. "- REVALIDACIÓN")
                capacitacion = " - ".join(partes[1:-1]).strip()
            else:
                dni = nombre_carpeta
                capacitacion = nombre_sin_ext
                fecha = ""

            registros.append({
                "dni": dni,
                "capacitacion": capacitacion,
                "fecha": fecha,
                "archivo": archivo
            })

    if not registros:
        print("⚠️ No se encontraron archivos PDF dentro de las subcarpetas.")
        return

    # Crear el libro de Excel
    wb = openpyxl.Workbook()
    ws = wb.active
    ws.title = "CERTIFICADOS_ISEM"

    encabezados = ["DNI", "CAPACITACIÓN", "FECHA", "NOMBRE DEL ARCHIVO"]
    ws.append(encabezados)

    # Estilo de encabezado
    header_fill = PatternFill(start_color="1F4E78", end_color="1F4E78", fill_type="solid")
    header_font = Font(color="FFFFFF", bold=True)
    for col_idx in range(1, len(encabezados) + 1):
        celda = ws.cell(row=1, column=col_idx)
        celda.fill = header_fill
        celda.font = header_font
        celda.alignment = Alignment(horizontal="center", vertical="center")

    # Insertar registros
    for reg in registros:
        ws.append([
            reg["dni"],
            reg["capacitacion"],
            reg["fecha"],
            reg["archivo"]
        ])

    # Conservar ceros a la izquierda en la columna DNI
    for row_idx in range(2, len(registros) + 2):
        ws.cell(row=row_idx, column=1).number_format = "@"

    # Ajustar ancho de columnas
    anchos = {"A": 14, "B": 55, "C": 15, "D": 75}
    for col_letra, ancho in anchos.items():
        ws.column_dimensions[col_letra].width = ancho

    wb.save(RUTA_REPORTE_EXCEL)
    print(f"✅ Se procesaron {len(registros)} certificados locales.")
    print(f"📊 Reporte Excel generado en:\n   {RUTA_REPORTE_EXCEL}")

if __name__ == "__main__":
    generar_reporte_desde_carpetas()