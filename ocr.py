"""
This code sample shows Prebuilt Receipt operations with the Azure AI Document Intelligence client library.
The async versions of the samples require Python 3.8 or later.

To learn more, please visit the documentation - Quickstart: Document Intelligence (formerly Form Recognizer) SDKs
https://learn.microsoft.com/azure/ai-services/document-intelligence/quickstarts/get-started-sdks-rest-api?pivots=programming-language-python
"""

from azure.core.credentials import AzureKeyCredential
from azure.ai.documentintelligence import DocumentIntelligenceClient
from azure.ai.documentintelligence.models import AnalyzeDocumentRequest
import sys
import json
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

"""
Remember to remove the key from your code when you're done, and never post it publicly. For production, use
secure methods to store and access your credentials. For more information, see 
https://docs.microsoft.com/en-us/azure/cognitive-services/cognitive-services-security?tabs=command-line%2Ccsharp#environment-variables-and-application-configuration
"""

if len(sys.argv) != 4:
    print("Usage: python ocr.py <key> <endpoint> <image_path>")
    sys.exit(1)

key = sys.argv[1]
endpoint = sys.argv[2]
image_path = sys.argv[3]

document_intelligence_client = DocumentIntelligenceClient(
    endpoint=endpoint, credential=AzureKeyCredential(key)
)

with open(image_path, "rb") as image:
    document_content = image.read()
    poller = document_intelligence_client.begin_analyze_document(
        "prebuilt-receipt",
        document_content,
    )

receipts = poller.result()

receipt_data = {}

for idx, receipt in enumerate(receipts.documents):
    print("--------Recognizing receipt #{}--------".format(idx + 1))
    receipt_type = receipt.doc_type
    if receipt_type:
        print("Receipt Type: {}".format(receipt_type))
        receipt_data["type"] = receipt_type

    merchant_name = receipt.fields.get("MerchantName")
    if merchant_name:
        print(
            "Merchant Name: {} has confidence: {}".format(
                merchant_name.value_string, merchant_name.confidence
            )
        )
        receipt_data["merchant"] = merchant_name.value_string

    transaction_date = receipt.fields.get("TransactionDate")
    if transaction_date:
        print(
            "Transaction Date: {} has confidence: {}".format(
                transaction_date.value_date, transaction_date.confidence
            )
        )
        receipt_data["date"] = transaction_date.value_date.strftime("%Y-%m-%d")
        receipt_data["time"] = transaction_date.value_date.strftime("%H:%M:%S")

    receipt_data["items"] = []
    total_amount = 0
    if receipt.fields.get("Items"):
        print("Receipt items:")
        for idx, item in enumerate(receipt.fields.get("Items").value_array):
            print("...Item #{}".format(idx + 1))
            item_data = {
                "description": item.value_object.get("Description", {}).value_string,
                "quantity": item.value_object.get("Quantity", {}).get("value_number", 1),
                "unitPrice": item.value_object.get("Price", {}).value_currency.amount if item.value_object.get("Price") else None
            }

            if item_total_price := item.value_object.get("TotalPrice"):
                price_value = item_total_price.content.replace("¥", "")
                item_data["totalPrice"] = price_value
                total_amount += int(price_value)
            else:
                item_data["totalPrice"] = "N/A"

            receipt_data["items"].append(item_data)

    subtotal = receipt.fields.get("Subtotal")
    if subtotal:
        print(
            "Subtotal: {} has confidence: {}".format(
                subtotal.value_currency.amount, subtotal.confidence
            )
        )
        receipt_data["subtotal"] = subtotal.value_currency.amount
    tax = receipt.fields.get("TotalTax")
    if tax and tax.value_currency:
        print(
            "Tax: {} has confidence: {}".format(
                tax.value_currency.amount, tax.confidence
            )
        )
        receipt_data["tax"] = tax.value_currency.amount

    tip = receipt.fields.get("Tip")
    if tip:
        print(
            "Tip: {} has confidence: {}".format(
                tip.value_currency.amount, tip.confidence
            )
        )
        receipt_data["tip"] = tip.value_currency.amount

    if total := receipt.fields.get("Total"):
        if total.value_currency:
            receipt_data["total"] = total.value_currency.amount
        else:
            receipt_data["total"] = str(total_amount)
    else:
        receipt_data["total"] = str(total_amount)

    print("--------------------------------------")

print(json.dumps(receipt_data, ensure_ascii=False))
