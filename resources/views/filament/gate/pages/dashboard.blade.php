<x-filament-panels::page>

@script
<script>
const onScanSuccess = (decodedText, decodedResult) => {
  $wire.processScan(decodedText);
}

const onScanFailure = (error) => {
  // handle scan failure, usually better to ignore and keep scanning.
  // for example:
  //console.warn(`Code scan error = ${error}`);
}

let html5QrcodeScanner = new Html5QrcodeScanner(
  "reader",
  { 
        fps: 10,
        useBarCodeDetectorIfSupported: true,
        rememberLastUsedCamera: true,
        showTorchButtonIfSupported: true,
    }
);
html5QrcodeScanner.render(onScanSuccess, onScanFailure);    
</script>
@endscript

<div id="reader" width="600px"></div>
</x-filament-panels::page>
