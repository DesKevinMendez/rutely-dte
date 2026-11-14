@props([
    'src1', 'src2', 'src3',
    'alt1' => '', 'alt2' => '', 'alt3' => '',
    'href1' => '#', 'href2' => '#', 'href3' => '#',
    'borderRadius' => '12px',
    'gap' => '10px'
])

<x-mj.section padding="20px 25px">
    <!-- Top Section: Full Width Image (Top Rounded Only) -->
    <x-mj.column width="100%" padding="0 0 {{ $gap }} 0">
        <x-mj.image 
            src="{{ $src1 }}" 
            alt="{{ $alt1 }}" 
            href="{{ $href1 }}" 
            borderRadius="{{ $borderRadius }} {{ $borderRadius }} 0 0" 
            width="100%" 
        />
    </x-mj.column>

    <!-- Bottom Section: Two Columns Seamless (Bottom Rounded Corners) -->
    <x-mj.group width="100%">
        <x-mj.column width="50%" padding="0">
            <x-mj.image 
                src="{{ $src2 }}" 
                alt="{{ $alt2 }}" 
                href="{{ $href2 }}" 
                borderRadius="0 0 0 {{ $borderRadius }}" 
                width="100%" 
            />
        </x-mj.column>

        <x-mj.column width="50%" padding="0">
            <x-mj.image 
                src="{{ $src3 }}" 
                alt="{{ $alt3 }}" 
                href="{{ $href3 }}" 
                borderRadius="0 0 {{ $borderRadius }} 0" 
                width="100%" 
            />
        </x-mj.column>
    </x-mj.group>
</x-mj.section>
