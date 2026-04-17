<?php

class MaterialPrice
{
    private ?int $id = null;
    private string $materialType = '';
    private float $pricePerKg = 0;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getMaterialType(): string
    {
        return $this->materialType;
    }

    public function setMaterialType(string $materialType): void
    {
        $this->materialType = $materialType;
    }

    public function getPricePerKg(): float
    {
        return $this->pricePerKg;
    }

    public function setPricePerKg(float $pricePerKg): void
    {
        $this->pricePerKg = $pricePerKg;
    }

    
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'material_type' => $this->materialType,
            'price_per_kg' => $this->pricePerKg,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
