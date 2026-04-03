#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import os
from lxml import etree
from signxml import XMLSigner, methods
from cryptography.hazmat.primitives.serialization import pkcs12, Encoding, PrivateFormat, NoEncryption


def salir_error(mensaje, codigo=1):
    print(mensaje)
    sys.exit(codigo)


def cargar_p12(ruta_p12, password):
    try:
        with open(ruta_p12, "rb") as f:
            p12_data = f.read()

        private_key, certificate, additional_certificates = pkcs12.load_key_and_certificates(
            p12_data,
            password.encode("utf-8")
        )

        if private_key is None or certificate is None:
            salir_error("No se pudo extraer la clave privada o el certificado del archivo .p12")

        key_pem = private_key.private_bytes(
            encoding=Encoding.PEM,
            format=PrivateFormat.PKCS8,
            encryption_algorithm=NoEncryption()
        )

        cert_pem = certificate.public_bytes(Encoding.PEM)

        return key_pem, cert_pem

    except Exception as e:
        salir_error(f"Error al cargar certificado .p12: {e}")


def firmar_xml(ruta_xml_entrada, ruta_p12, password_p12, ruta_xml_salida):
    if not os.path.exists(ruta_xml_entrada):
        salir_error(f"No existe el XML de entrada: {ruta_xml_entrada}")

    if not os.path.exists(ruta_p12):
        salir_error(f"No existe el certificado .p12: {ruta_p12}")

    try:
        parser = etree.XMLParser(remove_blank_text=False, resolve_entities=False)
        tree = etree.parse(ruta_xml_entrada, parser)
        root = tree.getroot()
    except Exception as e:
        salir_error(f"Error al leer XML: {e}")

    key_pem, cert_pem = cargar_p12(ruta_p12, password_p12)

    try:
        signer = XMLSigner(
            method=methods.enveloped,
            signature_algorithm="rsa-sha256",
            digest_algorithm="sha256"
        )

        signed_root = signer.sign(
            root,
            key=key_pem,
            cert=cert_pem
        )

        signed_tree = etree.ElementTree(signed_root)
        signed_tree.write(
            ruta_xml_salida,
            encoding="UTF-8",
            xml_declaration=True,
            pretty_print=False
        )

        if not os.path.exists(ruta_xml_salida) or os.path.getsize(ruta_xml_salida) <= 0:
            salir_error("Se intentó guardar el XML firmado, pero el archivo quedó vacío")

        print("XML firmado correctamente")

    except Exception as e:
        salir_error(f"Error al firmar XML: {e}")


def main():
    if len(sys.argv) != 5:
        salir_error("Uso: firmar_sri.py xml_entrada certificado.p12 password xml_salida")

    xml_entrada = sys.argv[1]
    certificado = sys.argv[2]
    password = sys.argv[3]
    xml_salida = sys.argv[4]

    firmar_xml(xml_entrada, certificado, password, xml_salida)


if __name__ == "__main__":
    main()