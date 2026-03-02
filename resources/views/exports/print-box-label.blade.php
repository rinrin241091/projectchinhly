<div id="printable">
  <div style="font-family: Arial; padding: 20px; width: 120mm; align-items: center; text-align: center; border: 1px solid black;">
    <p><strong>{{ $record?->shelf?->storage?->archival->name ?? '' }}</strong></p>
    <p><strong>HỘP SỐ</strong> </p>
    <p><strong>{{ $record->code }}</strong> </p>
    
    <p><strong>Số hồ sơ:</strong> </p>
    <p><strong>THBQ:</strong> {{ $record->type }}</p>

  </div>   
</div>
<hr>
    <button onclick="triggerPrint()" style="margin-top: 10px;">In ngay</button>

{{-- In tự động --}}
