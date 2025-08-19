/**
 * Extension of the default Array class that adds indexing methods
 */
class ArrayIndexed<T> extends Array<T> {
  dict: { [key: string]: any }
  key: string
  options: { autoId: boolean; unique: boolean }
  nextId: number

  constructor(indexKey: string, items?: any[], options?: { autoId?: boolean; unique?: boolean }) {
    if (items) {
      super(...items)
    } else {
      super()
    }
    this.key = indexKey
    this.dict = {}
    this.nextId = 1
    this.options = { ...{ autoId: false, unique: false }, ...options }

    if (this.length > 0) {
      let i = 0
      const _this = this

      if (this.options.autoId) {
        let maxId = 0
        let missingIds = false
        this.forEach(function (item) {
          if (item[_this.key] != null) {
            if (item[_this.key] > maxId) {
              maxId = item[_this.key]
            }
          } else {
            missingIds = true
          }
        })
        this.nextId = ++maxId
        if (missingIds) {
          this.forEach(function (item) {
            if (item[_this.key] == null) {
              item[_this.key] = _this.nextId
              _this.nextId++
            }
          })
        }
      }

      this.forEach(function (item) {
        _this.dict[item[_this.key]] = i
        i++
      })
    }
  }

  push(...items: T[]): number {
    items.forEach((item) => {
      if (!this.options.unique || (this.options.unique && !this.get(item[this.key]))) {
        super.push(item)
        if (this.options.autoId === true) {
          item[this.key] = this.nextId++
        }
        this.dict[item[this.key]] = this.length - 1
      }
    })
    return this.length
  }

  pop(): T | undefined {
    const item = this[this.length - 1]
    const id = item[this.key]
    const length = super.pop()
    delete this.dict[id]
    this.reindex()
    return super.pop()
  }

  update(data: { [key: string]: any }): T | boolean {
    if (data[this.key] != null) {
      const obj = this.get(data[this.key])
      for (const i in data) {
        obj[i] = data[i]
      }
      return obj
    }
    return false
  }

  get(id: number | string): any {
    return this.findById(id)
  }
  findById(id: number | string): T | undefined {
    const key: string | undefined = this.dict[id]
    return typeof key === "undefined" ? undefined : this[key]
  }

  deleteById(id: number | string): void {
    const index = this.dict[id]
    if (typeof index !== "undefined") {
      delete this.dict[id]
      this.splice(index, 1)
    }
  }

  delete(id: number | string): void {
    this.deleteById(id)
  }

  clear(): this {
    this.splice(0, this.length)
    this.length = 0
    this.reindex()
    return this
  }

  reindex(): void {
    const _this = this
    this.dict = {}
    this.forEach(function (item, index) {
      _this.dict[item[_this.key]] = index
    })
  }

  sort(compareFn?: (a: T, b: T) => number): this {
    super.sort(compareFn)
    this.reindex()
    return this
  }

  splice(start: number, deleteCount?: number): T[] {
    const res = super.splice(start, deleteCount)
    this.reindex()
    return res
  }

  toObject(): Record<string, T> {
    const object: Record<string, T> = {}
    for (const record of this) {
      object[record[this.key]] = record
    }
    return object
  }
}

export { ArrayIndexed }
