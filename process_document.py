import os
from google.cloud import documentai_v1 as documentai
import json
import sys

# Set Google Cloud credentials
os.environ['GOOGLE_APPLICATION_CREDENTIALS'] = os.path.join(os.path.dirname(__file__), 'config/document-ai-demo-456309-16d7de1fbb5e.json')

def process_document(file_path):
    try:
        # Initialize Document AI client
        client = documentai.DocumentProcessorServiceClient()

        # Configure the processor
        project_id = "document-ai-demo-456309"
        location = "us"
        processor_id = "39c93f152f4794ce"

        # The full resource name of the processor
        name = f"projects/{project_id}/locations/{location}/processors/{processor_id}"

        # Read the file
        with open(file_path, "rb") as image:
            image_content = image.read()

        # Get mime type
        mime_type = "image/png"  # You can enhance this to detect mime type
        if file_path.lower().endswith('.pdf'):
            mime_type = "application/pdf"
        elif file_path.lower().endswith('.jpg') or file_path.lower().endswith('.jpeg'):
            mime_type = "image/jpeg"

        # Create the document object
        raw_document = documentai.RawDocument(
            content=image_content,
            mime_type=mime_type
        )

        # Configure the process request
        request = documentai.ProcessRequest(
            name=name,
            raw_document=raw_document
        )

        # Process the document
        result = client.process_document(request=request)
        document = result.document

        # Return the extracted text
        response = {
            "success": True,
            "text": document.text,
            "error": None
        }
    except Exception as e:
        response = {
            "success": False,
            "text": None,
            "error": str(e)
        }

    # Print JSON response for PHP to capture
    print(json.dumps(response))

if __name__ == "__main__":
    if len(sys.argv) > 1:
        file_path = sys.argv[1]
        process_document(file_path) 