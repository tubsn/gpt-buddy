/* tslint:disable */
/* eslint-disable */

export class ImageProcessor {
    free(): void;
    [Symbol.dispose](): void;
    export_image(output_format: string, output_quality: number): Uint8Array;
    image_height(): number;
    image_width(): number;
    load_image(image_bytes: Uint8Array, mime_type: string): void;
    load_watermark(watermark_bytes: Uint8Array, mime_type: string, default_size: number): void;
    constructor(canvas: HTMLCanvasElement);
    pointer_down(pointer_x: number, pointer_y: number): string;
    pointer_move(pointer_x: number, pointer_y: number): void;
    pointer_up(): void;
    render(): void;
    set_opacity(opacity: number): void;
    set_padding(padding_horizontal: number, padding_vertical: number): void;
    set_position(position: string): void;
    set_watermark_width(watermark_width: number): void;
    watermark_width(): number;
}

export type InitInput = RequestInfo | URL | Response | BufferSource | WebAssembly.Module;

export interface InitOutput {
    readonly memory: WebAssembly.Memory;
    readonly __wbg_imageprocessor_free: (a: number, b: number) => void;
    readonly imageprocessor_export_image: (a: number, b: number, c: number, d: number, e: number) => void;
    readonly imageprocessor_image_height: (a: number) => number;
    readonly imageprocessor_image_width: (a: number) => number;
    readonly imageprocessor_load_image: (a: number, b: number, c: number, d: number, e: number, f: number) => void;
    readonly imageprocessor_load_watermark: (a: number, b: number, c: number, d: number, e: number, f: number, g: number) => void;
    readonly imageprocessor_new: (a: number, b: number) => void;
    readonly imageprocessor_pointer_down: (a: number, b: number, c: number, d: number) => void;
    readonly imageprocessor_pointer_move: (a: number, b: number, c: number, d: number) => void;
    readonly imageprocessor_pointer_up: (a: number) => void;
    readonly imageprocessor_render: (a: number, b: number) => void;
    readonly imageprocessor_set_opacity: (a: number, b: number) => void;
    readonly imageprocessor_set_padding: (a: number, b: number, c: number) => void;
    readonly imageprocessor_set_position: (a: number, b: number, c: number, d: number) => void;
    readonly imageprocessor_set_watermark_width: (a: number, b: number) => void;
    readonly imageprocessor_watermark_width: (a: number) => number;
    readonly __wbindgen_export: (a: number, b: number, c: number) => void;
    readonly __wbindgen_export2: (a: number) => void;
    readonly __wbindgen_export3: (a: number, b: number) => number;
    readonly __wbindgen_export4: (a: number, b: number, c: number, d: number) => number;
    readonly __wbindgen_add_to_stack_pointer: (a: number) => number;
}

export type SyncInitInput = BufferSource | WebAssembly.Module;

/**
 * Instantiates the given `module`, which can either be bytes or
 * a precompiled `WebAssembly.Module`.
 *
 * @param {{ module: SyncInitInput }} module - Passing `SyncInitInput` directly is deprecated.
 *
 * @returns {InitOutput}
 */
export function initSync(module: { module: SyncInitInput } | SyncInitInput): InitOutput;

/**
 * If `module_or_path` is {RequestInfo} or {URL}, makes a request and
 * for everything else, calls `WebAssembly.instantiate` directly.
 *
 * @param {{ module_or_path: InitInput | Promise<InitInput> }} module_or_path - Passing `InitInput` directly is deprecated.
 *
 * @returns {Promise<InitOutput>}
 */
export default function __wbg_init (module_or_path?: { module_or_path: InitInput | Promise<InitInput> } | InitInput | Promise<InitInput>): Promise<InitOutput>;
