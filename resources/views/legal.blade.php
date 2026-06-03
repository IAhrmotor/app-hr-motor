@extends('layouts.app')

@section('content')
    <main class="mx-auto w-full max-w-5xl flex-1 px-6 py-8 sm:py-10 lg:px-8">
        <section class="overflow-hidden rounded-[2rem] border border-brand-secondary/10 bg-white shadow-sm">
            <div class="border-b border-brand-secondary/10 px-6 py-8 sm:px-8 sm:py-10">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-primary">
                    Legal
                </p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-brand-secondary sm:text-4xl">
                    Aviso legal y política de privacidad
                </h1>
                <p class="mt-4 max-w-3xl text-base leading-7 text-brand-secondary/70">
                    Este documento reúne en una sola página la información legal de la aplicación interna,
                    sus condiciones de uso, la protección de datos y las reglas específicas del chat corporativo.
                </p>
            </div>

            <div class="space-y-8 px-6 py-8 sm:px-8">
                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            1
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Titularidad y ámbito de uso
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            La aplicación interna es titularidad de <strong>HISPANIA REAL MOTOR SL</strong>,
                            con C.I.F./N.I.F. <strong>B31851413</strong>, domicilio social en
                            <strong>Avenida de Navarra, 48, 31512 Fontellas (Navarra)</strong>, inscrita en el
                            Registro Mercantil de Navarra, Hoja NA 22866, Folio 1, Tomo 1654, Sección 8ª.
                        </p>
                        <p>
                            Teléfono de contacto: <strong>948402573</strong>. Correo electrónico:
                            <strong>dpd@hrmotor.com</strong>.
                        </p>
                        <p>
                            El acceso a esta herramienta está limitado a personal y perfiles autorizados por la empresa.
                            Su uso es exclusivamente interno, profesional y organizativo.
                        </p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            2
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Condiciones de uso de la aplicación
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            La persona usuaria se compromete a utilizar la aplicación de forma diligente, lícita y
                            respetuosa, evitando cualquier uso contrario a la normativa aplicable, a las políticas
                            internas de la empresa o a la confidencialidad exigible en el entorno laboral.
                        </p>
                        <p>
                            Queda prohibido emplearla para actividades ilícitas, para vulnerar derechos de terceros,
                            para difundir información no autorizada o para realizar acciones que puedan comprometer la
                            seguridad, disponibilidad o integridad de los sistemas.
                        </p>
                        <p>
                            Los contenidos, diseños, textos, imágenes, software y demás elementos de la aplicación están
                            protegidos por la normativa de propiedad intelectual e industrial y pertenecen a HISPANIA REAL MOTOR SL,
                            salvo indicación expresa en contrario.
                        </p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            3
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Información sobre protección de datos
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            Los datos personales tratados en esta aplicación se utilizan para la gestión interna de la
                            relación laboral y funcional, la coordinación entre departamentos, la organización de tareas,
                            la comunicación corporativa y, en su caso, la atención de incidencias, solicitudes y controles
                            administrativos.
                        </p>
                        <p>
                            La base jurídica del tratamiento podrá ser, según el caso, la ejecución de la relación
                            laboral o de servicios, el cumplimiento de obligaciones legales, el interés legítimo de la
                            empresa o el consentimiento cuando resulte necesario.
                        </p>
                        <p>
                            La información detallada sobre responsables, derechos y reclamaciones se completa en esta
                            misma página y en la política interna de protección de datos de la empresa.
                        </p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            4
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Chat corporativo
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            El chat corporativo es una herramienta interna destinada a facilitar la comunicación entre
                            personas autorizadas. No debe utilizarse para compartir contraseñas, credenciales, datos
                            bancarios, documentación confidencial innecesaria, datos de salud ni información especialmente sensible
                            salvo que exista una necesidad profesional y una base legítima que lo ampare.
                        </p>
                        <p>
                            Las conversaciones pueden ser consultadas por los participantes de cada conversación y, de
                            forma excepcional, por personal autorizado cuando exista una causa justificada vinculada a
                            seguridad, cumplimiento normativo, investigación de incidencias, soporte técnico o control interno.
                        </p>
                        <p>
                            Todo acceso administrativo queda registrado y auditado.
                        </p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            5
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Retención de mensajes, logs y backups
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            Los mensajes del chat corporativo se conservan, como regla general, durante un plazo máximo
                            de seis meses. La purga automática se ejecuta diariamente a las 05:30, salvo que exista una
                            incidencia de seguridad, una obligación legal o una conservación excepcional autorizada.
                        </p>
                        <p>
                            Los logs técnicos del chat y de la aplicación registran eventos necesarios para mantenimiento,
                            seguridad, errores, trazabilidad y auditoría. No incluyen el contenido íntegro de los mensajes.
                        </p>
                        <p>
                            La aplicación se aloja en infraestructura controlada por la empresa o por proveedores
                            tecnológicos autorizados. La base de datos y los ficheros persistentes se almacenan en
                            entornos controlados con acceso restringido al personal autorizado de IT.
                        </p>
                        <p>
                            Las copias de seguridad se realizan de forma periódica, se conservan durante un máximo de 30
                            días, se almacenan cifradas y tienen acceso restringido al personal autorizado de IT.
                        </p>
                        <p>
                            Los backups externos podrán almacenarse en una ubicación corporativa de Microsoft
                            OneDrive/SharePoint controlada por IT.
                        </p>
                        <p>
                            Los proveedores tecnológicos que puedan intervenir en el alojamiento, mantenimiento,
                            seguridad o copias de seguridad deberán contar con las garantías contractuales exigibles.
                        </p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            6
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Accesos administrativos y auditoría
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            El acceso administrativo a conversaciones, historiales, logs o ajustes sensibles se limita a
                            perfiles autorizados y requiere una causa justificada. Siempre que sea posible, ese acceso se
                            realiza de forma temporal, proporcional y registrada.
                        </p>
                        <p>
                            Los accesos y operaciones relevantes pueden quedar reflejados en registros de auditoría para
                            garantizar la seguridad, la trazabilidad y la correcta investigación de incidencias.
                        </p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            7
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Seguridad
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            La aplicación incorpora medidas técnicas y organizativas razonables para preservar la
                            confidencialidad, integridad y disponibilidad de la información, incluyendo control de acceso,
                            registro de actividad, separación de permisos y mecanismos de copia de seguridad.
                        </p>
                        <p>
                            No obstante, ninguna medida resulta absolutamente infalible, por lo que la persona usuaria
                            debe mantener la confidencialidad de sus credenciales, bloquear la sesión cuando deje el
                            equipo desatendido y evitar compartir información sensible fuera de los canales autorizados.
                        </p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            8
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Cookies técnicas y sesión
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            Esta aplicación utiliza únicamente cookies y mecanismos técnicos de sesión necesarios para
                            mantener la autenticación, preservar preferencias básicas de navegación y garantizar el
                            funcionamiento correcto de la plataforma interna.
                        </p>
                        <p>
                            No se prevé el uso de cookies publicitarias ni de seguimiento con fines comerciales dentro de
                            esta aplicación interna. Si en el futuro se incorporasen funcionalidades que requiriesen
                            tratamientos adicionales, se informará de forma previa y adecuada.
                        </p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            9
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Derechos de los usuarios
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            Las personas interesadas pueden solicitar acceso, rectificación, supresión, oposición,
                            limitación del tratamiento y portabilidad cuando resulte aplicable, así como no ser objeto de
                            decisiones basadas únicamente en tratamientos automatizados, de acuerdo con la normativa de
                            protección de datos.
                        </p>
                        <p>
                            Para ejercer estos derechos pueden dirigirse por escrito a la dirección indicada en el
                            encabezado o al correo electrónico <strong>dpd@hrmotor.com</strong>, acreditando su identidad cuando
                            sea necesario para verificar la solicitud.
                        </p>
                        <p>
                            Si consideran que sus derechos no han sido atendidos correctamente, pueden presentar una
                            reclamación ante la Agencia Española de Protección de Datos.
                        </p>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-brand-secondary">
                            10
                        </span>
                        <h2 class="text-2xl font-bold tracking-tight text-brand-secondary">
                            Cambios en la política
                        </h2>
                    </div>

                    <div class="space-y-3 text-sm leading-7 text-brand-secondary/80">
                        <p>
                            HISPANIA REAL MOTOR SL podrá actualizar esta página cuando resulte necesario para reflejar
                            cambios organizativos, legales, técnicos o de seguridad. La versión vigente será siempre la
                            publicada en la propia aplicación.
                        </p>
                        <p>
                            Última actualización: <strong>2 de junio de 2026</strong>.
                        </p>
                    </div>
                </section>
            </div>
        </section>
    </main>
@endsection
