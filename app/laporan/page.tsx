"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import * as XLSX from "xlsx";
import { supabase } from "@/lib/supabase";

type Pegawai = {
  id: number;
  nama: string;
  nip: string;
};

type Pelatihan = {
  id: number;
  nama_pelatihan: string;
};

type Riwayat = {
  id: number;
  pegawai_id: number;
  pelatihan_id: number;
  status: string | null;
  tanggal_update: string | null;
};

type Laporan = {
  pegawai: string;
  nip: string;
  pelatihan: string;
  status: string;
  tanggal_update: string | null;
};

const STATUS = [
  "SEMUA",
  "SUDAH",
  "DIAJUKAN",
  "BELUM",
  "BELUM_DINPUT",
];

export default function LaporanPage() {
  const [data, setData] =
    useState<Laporan[]>([]);

  const [pelatihan, setPelatihan] =
    useState<Pelatihan[]>([]);

  const [filterPelatihan, setFilterPelatihan] =
    useState("SEMUA");

  const [filterStatus, setFilterStatus] =
    useState("SEMUA");

  const [search, setSearch] =
    useState("");

  const [loading, setLoading] =
    useState(true);

  const [error, setError] =
    useState("");

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    setLoading(true);
    setError("");

    try {
      const [
        pegawaiResult,
        pelatihanResult,
        riwayatResult,
      ] = await Promise.all([
        supabase
          .from("pegawai")
          .select("id,nama,nip"),

        supabase
          .from("pelatihan")
          .select(
            "id,nama_pelatihan"
          )
          .order(
            "nama_pelatihan"
          ),

        supabase
          .from("riwayat_pelatihan")
          .select(
            "id,pegawai_id,pelatihan_id,status,tanggal_update"
          )
          .order(
            "pelatihan_id"
          ),
      ]);

      if (pegawaiResult.error) {
        throw new Error(
          pegawaiResult.error.message
        );
      }

      if (pelatihanResult.error) {
        throw new Error(
          pelatihanResult.error.message
        );
      }

      if (riwayatResult.error) {
        throw new Error(
          riwayatResult.error.message
        );
      }

      const pegawai =
        (pegawaiResult.data as Pegawai[]) ||
        [];

      const daftarPelatihan =
        (pelatihanResult.data as Pelatihan[]) ||
        [];

      const riwayat =
        (riwayatResult.data as Riwayat[]) ||
        [];

      setPelatihan(
        daftarPelatihan
      );

      const hasil =
        riwayat.map((r) => {
          const p =
            pegawai.find(
              (x) =>
                x.id ===
                r.pegawai_id
            );

          const pl =
            daftarPelatihan.find(
              (x) =>
                x.id ===
                r.pelatihan_id
            );

          return {
            pegawai:
              p?.nama || "-",
            nip:
              p?.nip || "-",
            pelatihan:
              pl?.nama_pelatihan ||
              "-",
            status:
              r.status ||
              "BELUM_DINPUT",
            tanggal_update:
              r.tanggal_update,
          };
        });

      setData(hasil);
    } catch (err) {
      console.error(err);

      setError(
        err instanceof Error
          ? err.message
          : "Gagal mengambil laporan."
      );
    } finally {
      setLoading(false);
    }
  }

  const filtered =
    useMemo(() => {
      const keyword =
        search
          .trim()
          .toLowerCase();

      return data.filter(
        (item) => {
          const cocokPelatihan =
            filterPelatihan ===
              "SEMUA" ||
            item.pelatihan ===
              filterPelatihan;

          const cocokStatus =
            filterStatus ===
              "SEMUA" ||
            item.status ===
              filterStatus;

          const cocokSearch =
            !keyword ||
            item.pegawai
              .toLowerCase()
              .includes(keyword) ||
            item.nip
              .toLowerCase()
              .includes(keyword) ||
            item.pelatihan
              .toLowerCase()
              .includes(keyword);

          return (
            cocokPelatihan &&
            cocokStatus &&
            cocokSearch
          );
        }
      );
    }, [
      data,
      search,
      filterPelatihan,
      filterStatus,
    ]);

  function exportExcel() {
    if (filtered.length === 0) {
      alert("Tidak ada data yang sesuai filter untuk diexport.");
      return;
    }

    try {
      const rows = filtered.map((item, index) => ({
        No: index + 1,
        "Nama Pegawai": item.pegawai,
        NIP: item.nip,
        Pelatihan: item.pelatihan,
        Status:
          item.status === "BELUM_DINPUT"
            ? "BELUM DIINPUT"
            : item.status,
        "Tanggal Update": item.tanggal_update
          ? new Date(item.tanggal_update).toLocaleString("id-ID")
          : "-",
      }));

      const worksheet = XLSX.utils.json_to_sheet(rows);

      worksheet["!cols"] = [
        { wch: 6 },
        { wch: 30 },
        { wch: 22 },
        { wch: 38 },
        { wch: 18 },
        { wch: 24 },
      ];

      const workbook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(
        workbook,
        worksheet,
        "Laporan Pelatihan"
      );

      const ringkasan = XLSX.utils.aoa_to_sheet([
        ["RINGKASAN LAPORAN PELATIHAN ASN"],
        [],
        ["Filter Nama/NIP/Pelatihan", search || "Semua"],
        [
          "Filter Pelatihan",
          filterPelatihan === "SEMUA"
            ? "Semua"
            : filterPelatihan,
        ],
        [
          "Filter Status",
          filterStatus === "SEMUA"
            ? "Semua"
            : filterStatus === "BELUM_DINPUT"
              ? "BELUM DIINPUT"
              : filterStatus,
        ],
        [],
        ["Jumlah Data", filtered.length],
        [
          "Pegawai Unik",
          new Set(filtered.map((x) => x.pegawai_id)).size,
        ],
        [
          "SUDAH",
          new Set(
            filtered
              .filter((x) => x.status === "SUDAH")
              .map((x) => x.pegawai_id)
          ).size,
        ],
        [
          "DIAJUKAN",
          new Set(
            filtered
              .filter((x) => x.status === "DIAJUKAN")
              .map((x) => x.pegawai_id)
          ).size,
        ],
        [
          "BELUM",
          new Set(
            filtered
              .filter((x) => x.status === "BELUM")
              .map((x) => x.pegawai_id)
          ).size,
        ],
        [
          "BELUM DIINPUT",
          new Set(
            filtered
              .filter((x) => x.status === "BELUM_DINPUT")
              .map((x) => x.pegawai_id)
          ).size,
        ],
      ]);

      ringkasan["!cols"] = [
        { wch: 32 },
        { wch: 42 },
      ];

      XLSX.utils.book_append_sheet(
        workbook,
        ringkasan,
        "Ringkasan"
      );

      const tanggal = new Date()
        .toISOString()
        .slice(0, 10);

      XLSX.writeFile(
        workbook,
        `laporan-pelatihan-${tanggal}.xlsx`
      );
    } catch (err) {
      console.error("EXPORT EXCEL ERROR:", err);
      alert("Gagal membuat file Excel.");
    }
  }

  return (
    <main className="min-h-screen bg-slate-100 p-6">

      <div className="mx-auto max-w-7xl">

        <div className="mb-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

          <div>

            <Link
              href="/"
              className="font-semibold text-blue-600"
            >
              ← Dashboard
            </Link>

            <h1 className="mt-3 text-3xl font-bold">
              Laporan Pelatihan
            </h1>

            <p className="mt-2 text-slate-600">
              Laporan status pelatihan seluruh pegawai
            </p>

          </div>

          <div className="flex flex-wrap gap-3">
            <button
              onClick={loadData}
              disabled={loading}
              className="rounded-xl bg-slate-800 px-5 py-3 font-semibold text-white hover:bg-slate-900 disabled:opacity-50"
            >
              {loading ? "Memuat..." : "↻ Refresh"}
            </button>

            <button
              onClick={exportExcel}
              disabled={loading || filtered.length === 0}
              className="rounded-xl bg-emerald-600 px-5 py-3 font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
            >
              📥 Export Excel
            </button>
          </div>

        </div>

        {error && (
          <div className="mb-6 rounded-xl bg-red-50 p-4 text-red-700">
            {error}
          </div>
        )}

        <div className="mb-6 rounded-2xl bg-white p-6 shadow-sm">

          <div className="grid gap-4 md:grid-cols-3">

            <input
              value={search}
              onChange={(e) =>
                setSearch(
                  e.target.value
                )
              }
              placeholder="Cari nama / NIP / pelatihan..."
              className="rounded-xl border px-4 py-3 text-black outline-none focus:border-blue-500"
            />

            <select
              value={filterPelatihan}
              onChange={(e) =>
                setFilterPelatihan(
                  e.target.value
                )
              }
              className="rounded-xl border px-4 py-3 text-black"
            >
              <option value="SEMUA">
                Semua Pelatihan
              </option>

              {pelatihan.map(
                (item) => (
                  <option
                    key={item.id}
                    value={
                      item.nama_pelatihan
                    }
                  >
                    {
                      item.nama_pelatihan
                    }
                  </option>
                )
              )}
            </select>

            <select
              value={filterStatus}
              onChange={(e) =>
                setFilterStatus(
                  e.target.value
                )
              }
              className="rounded-xl border px-4 py-3 text-black"
            >
              {STATUS.map(
                (status) => (
                  <option
                    key={status}
                    value={status}
                  >
                    {status ===
                    "BELUM_DINPUT"
                      ? "BELUM DIINPUT"
                      : status}
                  </option>
                )
              )}
            </select>

          </div>

        </div>

        <div className="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">

          <div className="rounded-xl bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">
              Data
            </p>
            <p className="mt-1 text-3xl font-bold">
              {filtered.length}
            </p>
          </div>

          <div className="rounded-xl bg-green-50 p-5">
            <p className="text-sm text-green-700">
              Sudah
            </p>
            <p className="mt-1 text-3xl font-bold text-green-700">
              {
                filtered.filter(
                  (x) =>
                    x.status ===
                    "SUDAH"
                ).length
              }
            </p>
          </div>

          <div className="rounded-xl bg-yellow-50 p-5">
            <p className="text-sm text-yellow-700">
              Diajukan
            </p>
            <p className="mt-1 text-3xl font-bold text-yellow-700">
              {
                filtered.filter(
                  (x) =>
                    x.status ===
                    "DIAJUKAN"
                ).length
              }
            </p>
          </div>

          <div className="rounded-xl bg-slate-100 p-5">
            <p className="text-sm text-slate-700">
              Belum
            </p>
            <p className="mt-1 text-3xl font-bold text-slate-700">
              {
                filtered.filter(
                  (x) => x.status === "BELUM"
                ).length
              }
            </p>
          </div>

          <div className="rounded-xl bg-slate-50 p-5">
            <p className="text-sm text-slate-600">
              Belum Diinput
            </p>
            <p className="mt-1 text-3xl font-bold">
              {
                filtered.filter(
                  (x) =>
                    x.status ===
                    "BELUM_DINPUT"
                ).length
              }
            </p>
          </div>

        </div>

        <div className="overflow-x-auto rounded-2xl bg-white shadow-sm">

          <table className="min-w-full">

            <thead className="bg-slate-50">

              <tr>
                <th className="px-5 py-4 text-left text-sm">
                  No
                </th>

                <th className="px-5 py-4 text-left text-sm">
                  Pegawai
                </th>

                <th className="px-5 py-4 text-left text-sm">
                  NIP
                </th>

                <th className="px-5 py-4 text-left text-sm">
                  Pelatihan
                </th>

                <th className="px-5 py-4 text-left text-sm">
                  Status
                </th>

                <th className="px-5 py-4 text-left text-sm">
                  Update
                </th>
              </tr>

            </thead>

            <tbody className="divide-y">

              {loading ? (
                <tr>
                  <td
                    colSpan={6}
                    className="p-10 text-center"
                  >
                    Memuat laporan...
                  </td>
                </tr>
              ) : filtered.length ===
                0 ? (
                <tr>
                  <td
                    colSpan={6}
                    className="p-10 text-center text-slate-500"
                  >
                    Tidak ada data.
                  </td>
                </tr>
              ) : (
                filtered.map(
                  (item, index) => (
                    <tr
                      key={`${item.nip}-${item.pelatihan}-${index}`}
                      className="hover:bg-slate-50"
                    >

                      <td className="px-5 py-4">
                        {index + 1}
                      </td>

                      <td className="px-5 py-4 font-semibold">
                        {item.pegawai}
                      </td>

                      <td className="px-5 py-4">
                        {item.nip}
                      </td>

                      <td className="px-5 py-4">
                        {item.pelatihan}
                      </td>

                      <td className="px-5 py-4">
                        <span className="rounded-lg bg-slate-100 px-3 py-2 text-xs font-bold">
                          {item.status ===
                          "BELUM_DINPUT"
                            ? "BELUM DIINPUT"
                            : item.status}
                        </span>
                      </td>

                      <td className="px-5 py-4 text-sm text-slate-500">
                        {item.tanggal_update
                          ? new Date(
                              item.tanggal_update
                            ).toLocaleString(
                              "id-ID"
                            )
                          : "-"}
                      </td>

                    </tr>
                  )
                )
              )}

            </tbody>

          </table>

        </div>

        <div className="mt-8 text-center text-sm text-slate-400">
          Sistem Data Pelatihan ASN
        </div>

      </div>
    </main>
  );
}