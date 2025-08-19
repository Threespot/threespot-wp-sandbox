import { Events } from "../../Core/Events"
import { FormBuilder } from "../FormBuilder"

/**
 *
 */
export interface FormElementInterface {
  name: string
  label: string
  type: string
  db_type: string
  value: any
  domElements: {
    main?: HTMLElement
    edit?: HTMLElement
    // fields?: {[key:string]: HTMLInputElement},
    [key: string]: HTMLElement
  }
  inputs: {
    [key: string]:
      | HTMLInputElement
      | HTMLSelectElement
      | HTMLTextAreaElement
      | HTMLButtonElement
      | RadioNodeList
  }
  templates: {
    main?: (data: Record<string, any>) => string
    edit?: (data: Record<string, any>) => string
    [key: string]: (data: Record<string, any>) => string
  }
  formBuilder: FormBuilder
  searchable?: boolean
  options?: Array<{ [key: string]: any }>
  optionsDict?: { [key: string]: any }
  help?: string
  placeholder?: string
  parameterName?: string
  parameterNameShort?: string
  nameChanged: boolean
  visible?: boolean
  readonly?: boolean
  protected?: boolean
  external: { [key: string]: any }

  mapIsGeo: boolean
  events: Events
  namespace: string
  editMode: boolean
  filtersMode: boolean

  init(): void

  initEditor(): void

  setDomElements(): void

  setEventHandlers(): void

  setEditorEventHandlers(): void

  getEditor(): HTMLElement

  destroyEditor(): void

  getData(): { name: string; value: any }

  getDataForTemplate(): any

  getSchema(): any

  destroy(): void

  show(): void

  hide(): void

  setValue(value: any, updateInput?: boolean): void

  setInputValue(value: any): void

  getValue(): any
  redraw(): void
  setExternal(params: any): void
}
