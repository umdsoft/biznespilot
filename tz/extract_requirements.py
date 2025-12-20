import zipfile
import xml.etree.ElementTree as ET
import re
import sys

def extract_text_from_docx(docx_path):
    """Extract text content from a .docx file"""
    text_content = []

    try:
        with zipfile.ZipFile(docx_path, 'r') as zip_ref:
            xml_content = zip_ref.read('word/document.xml')
            root = ET.fromstring(xml_content)

            # Define namespace
            namespace = {'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}

            # Extract all text elements
            for text_elem in root.findall('.//w:t', namespace):
                if text_elem.text:
                    text_content.append(text_elem.text)

        return ' '.join(text_content)
    except Exception as e:
        return f"Error reading {docx_path}: {str(e)}"

def find_key_sections(text):
    """Extract key sections from text"""
    sections = {
        'modules': [],
        'features': [],
        'apis': [],
        'algorithms': [],
        'requirements': []
    }

    # Split into lines for better processing
    lines = text.split('\n')

    # Look for key section headers and content
    current_section = None
    for line in lines:
        line = line.strip()
        if not line:
            continue

        # Detect section headers
        lower_line = line.lower()
        if any(keyword in lower_line for keyword in ['модул', 'module', 'модуль']):
            current_section = 'modules'
        elif any(keyword in lower_line for keyword in ['функция', 'feature', 'функционал']):
            current_section = 'features'
        elif any(keyword in lower_line for keyword in ['api', 'endpoint']):
            current_section = 'apis'
        elif any(keyword in lower_line for keyword in ['алгоритм', 'algorithm']):
            current_section = 'algorithms'
        elif any(keyword in lower_line for keyword in ['требование', 'requirement', 'talab']):
            current_section = 'requirements'

        # Store content in appropriate section
        if current_section and len(line) > 10:
            sections[current_section].append(line)

    return sections

if __name__ == '__main__':
    docs = [
        'BiznesPilot_TRD_v5_1.0.docx',
        'BiznesPilot_Algorithms_v1.docx',
        'BiznesPilot_AI_Learning_System_V2_Full.docx'
    ]

    print("=" * 80)
    print("BIZNESPILOT - TEXNIK TALABLAR TAHLILI")
    print("=" * 80)

    for doc in docs:
        print(f"\n\n{'='*80}")
        print(f"HUJJAT: {doc}")
        print(f"{'='*80}\n")

        text = extract_text_from_docx(doc)

        # Print first 5000 characters to get overview
        print("HUJJAT MAZMUNI (QISQARTIRILIB):")
        print("-" * 80)
        print(text[:5000])
        print("\n...")
        print(f"\nJAMI BELGILAR: {len(text)}")
