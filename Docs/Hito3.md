# Hito 3 : Creación de un contenedor para pruebas

## Elección de un contenedor base

Para esta parte del proyecto se eligió un contenedor base para el proyecto en PHP y PgAdmin. Se utilizara PHP Docker Image, el cual nos permite utilizar la imagen oficial de PHP desde Docker Hub; y de la misma manera con postgres ya que es el sistema de gestión de bases de datos relacionales de objetos que usaremos para el proyecto.

Realizar la instalación de dichas imagenes

![Hito3_2](img/Hito3_2.png)

![Hito3_3](img/Hito3_3.png)

Aqui comprobaremos que las imagenes se encuentran dentro del contenedor

![Hito3_4](img/Hito3_4.png)

![Hito3_5](img/Hito3_5.png)




Primero realizara la configuración del Dockerfile con PHP 8.2.14 con Apache y Dockerfile para PgAdmin:

![Hito3_0](img/Hito3_0.png)

Luego pasaremos a la configuración de docker-compose.yml, como se muestra en la siguiente imagen

![Hito3_0](img/Hito3_1.png)



## Elección de un contenedor base


    
  - **Primero:** Se instala el framework [PHPUnit](https://linux.how2shout.com/3-ways-to-install-phpunit-in-ubuntu-22-04-or-20-04-lts/)
  - **Segundo:** Verificar si la instación fue exito con el siguiente comando **$ phpunit --version**
  

   - Opción Afj1020.php
     
   ![Test4](img/Test4.png)

