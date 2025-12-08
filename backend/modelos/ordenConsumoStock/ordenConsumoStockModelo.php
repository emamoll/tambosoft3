<?php

class OrdenConsumoStock
{
  private $id;
  private $ordenId;
  private $stockId;
  private $cantidadConsumida;

  public function __construct(
    $id,
    $ordenId,
    $stockId,
    $cantidadConsumida
  ) {
    $this->id = $id;
    $this->ordenId = $ordenId;
    $this->stockId = $stockId;
    $this->cantidadConsumida = $cantidadConsumida;
  }

  // Getters
  public function getId()
  {
    return $this->id;
  }

  public function getOrdenId()
  {
    return $this->ordenId;
  }

  public function getStockId()
  {
    return $this->stockId;
  }

  public function getCantidadConsumida()
  {
    return $this->cantidadConsumida;
  }
}